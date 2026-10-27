<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ConstanciaVisibilityByName;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;
use Mockery;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConstanciasHiddenStatusTest extends TestCase
{
    #[DataProvider('roles')]
    public function test_status_99_is_hidden_from_lists_searches_counts_and_filter_options(string $role): void
    {
        $query = $this->visibleQuery($role);

        $this->assertSame(['VISIBLE-1', 'VISIBLE-2', 'VISIBLE-3'], array_column(
            $this->rows((clone $query)->orderBy('c.id_respuesta')), 'id_respuesta'
        ));
        $this->assertSame(3, (int) $this->rows((clone $query)->selectRaw('COUNT(*) AS total'))[0]->total);
        $this->assertSame([], $this->rows((clone $query)->where('c.nombre_curso', 'CURSO OCULTO')));
        $this->assertSame([], $this->rows((clone $query)->where('c.estatus', 99)));
        $this->assertSame(['VISIBLE'], array_column(
            $this->rows((clone $query)->select('c.val_plantilla')->distinct()), 'val_plantilla'
        ));
    }

    #[DataProvider('roles')]
    public function test_status_99_cannot_be_selected_by_id_for_detail_or_decisions(string $role): void
    {
        $query = $this->visibleQuery($role);

        $this->assertSame([], $this->rows((clone $query)->where('c.id_respuesta', 'OCULTA')));
        $this->assertCount(1, $this->rows((clone $query)->where('c.id_respuesta', 'VISIBLE-1')));
    }

    public function test_exclusion_uses_the_requested_table_alias(): void
    {
        $query = $this->visibleQuery('admin_oc', 'constancia');

        $this->assertSame([], $this->rows($query->where('constancia.id_respuesta', 'OCULTA')));
    }

    public static function roles(): array
    {
        return [
            'administrador central' => ['admin_oc'],
            'administrador' => ['admin'],
            'supervisor central' => ['supervisor_oc'],
            'revisor estatal' => ['revisor_est'],
            'supervisor estatal' => ['supervisor_est'],
        ];
    }

    private function visibleQuery(string $role, string $alias = 'c'): Builder
    {
        // Base aislada: estas pruebas no usan la conexión ni los registros del sistema.
        $pdo = new PDO('sqlite::memory:');
        $pdo->sqliteCreateFunction('BTRIM', fn ($value) => trim((string) $value), 1);
        $connection = new SQLiteConnection($pdo);
        $connection->statement('CREATE TABLE constancias (
            id_respuesta TEXT, estatus INTEGER, nombre_curso TEXT,
            entidad TEXT, tipo_nomina TEXT, clues TEXT, val_plantilla TEXT
        )');

        foreach ([1, 2, 3, 99] as $status) {
            $connection->table('constancias')->insert([
                'id_respuesta' => $status === 99 ? 'OCULTA' : "VISIBLE-{$status}",
                'estatus' => $status,
                'nombre_curso' => $status === 99 ? 'CURSO OCULTO' : 'CURSO VISIBLE',
                'entidad' => 'ENTIDAD',
                'tipo_nomina' => 'NOMINA',
                'clues' => 'CLUES-PRUEBA',
                'val_plantilla' => $status === 99 ? 'SOLO OCULTA' : 'VISIBLE',
            ]);
        }

        DB::shouldReceive('raw')->andReturnUsing(fn ($sql) => new Expression($sql));

        $userQuery = Mockery::mock(Builder::class);
        DB::shouldReceive('table')->with('administracion.users as u')->andReturn($userQuery);
        $userQuery->shouldReceive('leftJoin', 'where', 'select', 'addSelect')->andReturnSelf();
        $userQuery->shouldReceive('first')->andReturn((object) [
            'entidad_nombre' => 'ENTIDAD',
            'tipo_nomina_codigo' => 'NOMINA',
            'clues_codigo' => 'CLUES-PRUEBA',
        ]);

        $roles = Mockery::mock(Builder::class);
        DB::shouldReceive('table')->with('administracion.user_roles as ur')->andReturn($roles);
        $roles->shouldReceive('join', 'where')->andReturnSelf();
        $roles->shouldReceive('pluck')->with('r.code')->andReturn(collect([$role]));

        $tables = Mockery::mock(Builder::class);
        DB::shouldReceive('table')->with('information_schema.tables')->andReturn($tables);
        $tables->shouldReceive('where')->andReturnSelf();
        $tables->shouldReceive('exists')->andReturn(false);

        $columns = Mockery::mock(Builder::class);
        DB::shouldReceive('table')->with('information_schema.columns')->andReturn($columns);
        $columns->shouldReceive('where', 'orderBy')->andReturnSelf();
        $columns->shouldReceive('pluck')->with('column_name')->andReturn(collect([]));

        $user = new User();
        $user->id = 42;
        $query = $connection->table("constancias as {$alias}");
        ConstanciaVisibilityByName::apply($query, $user, $alias);

        return $query;
    }

    private function rows(Builder $query): array
    {
        // Los campos de la fixture ya son TEXT; SQLite no admite el cast ::text de PostgreSQL.
        return $query->getConnection()->select(
            str_replace('::text', '', $query->toSql()), $query->getBindings()
        );
    }
}
