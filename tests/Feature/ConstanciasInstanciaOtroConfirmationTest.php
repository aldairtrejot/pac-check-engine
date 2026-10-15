<?php

namespace Tests\Feature;

use App\Http\Controllers\Constancias\UpdateEstatusConstanciasController;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConstanciasInstanciaOtroConfirmationTest extends TestCase
{
    public function test_acceptance_requests_confirmation_before_writes_or_catalog_validation(): void
    {
        $this->mockVisibleConstancia('Revisar la institución capturada.');

        $response = $this->update(['accion' => 'ACEPTAR']);

        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['status']);
        $this->assertTrue($data['requires_confirmation']);
        $this->assertTrue($data['requires_instancia_otro_confirmation']);
        $this->assertSame('Revisar la institución capturada.', $data['instancia_otro']);
        Mail::assertNothingSent();
    }

    public function test_zero_is_an_observation_and_requires_confirmation(): void
    {
        $this->mockVisibleConstancia('0');

        $data = $this->update(['accion' => 'ACEPTAR'])->getData(true);

        $this->assertTrue($data['requires_instancia_otro_confirmation']);
        $this->assertSame('0', $data['instancia_otro']);
    }

    #[DataProvider('emptyObservations')]
    public function test_empty_observations_continue_the_existing_acceptance_flow(?string $observation): void
    {
        $this->mockVisibleConstancia($observation);
        $this->expectExistingCatalogValidation();

        $response = $this->update(['accion' => 'ACEPTAR']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayNotHasKey('requires_instancia_otro_confirmation', $response->getData(true));
    }

    public static function emptyObservations(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'whitespace' => [" \t\r\n "],
        ];
    }

    public function test_explicit_confirmation_continues_the_existing_acceptance_flow(): void
    {
        $this->mockVisibleConstancia('Revisar la institución capturada.');
        $this->expectExistingCatalogValidation();

        $response = $this->update([
            'accion' => 'ACEPTAR',
            'confirmar_instancia_otro' => true,
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayNotHasKey('requires_instancia_otro_confirmation', $response->getData(true));
    }

    public function test_rejection_does_not_request_observation_confirmation(): void
    {
        $this->mockVisibleConstancia('Revisar la institución capturada.', 2);

        $response = $this->update([
            'accion' => 'RECHAZAR',
            'motivo' => 'La evidencia no corresponde.',
        ]);

        // El rechazo conserva su validación de estatus aunque haya observación.
        $this->assertSame(409, $response->getStatusCode());
        $this->assertArrayNotHasKey('requires_instancia_otro_confirmation', $response->getData(true));
    }

    private function update(array $payload)
    {
        return (new UpdateEstatusConstanciasController())->update(
            Request::create('/constancias/estatus', 'POST', [
                'id_respuesta' => 'CONSTANCIA-PRUEBA',
                ...$payload,
            ])
        );
    }

    private function mockVisibleConstancia(?string $observation, int $status = 1): void
    {
        $user = new User();
        $user->id = 42;
        Auth::setUser($user);
        Mail::fake();

        // No conexión a la base configurada ni permisos de escritura en estas pruebas.
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($callback) => $callback());
        DB::shouldReceive('raw')->andReturnUsing(fn ($value) => new Expression($value));
        DB::shouldNotReceive('select');

        $record = Mockery::mock(Builder::class);
        DB::shouldReceive('table')->with('public.tbl_constancias as c')->once()->andReturn($record);
        $record->shouldReceive('whereNotNull', 'whereRaw', 'select', 'where', 'lockForUpdate')->andReturnSelf();
        $record->shouldNotReceive('update', 'insert', 'delete');
        $record->shouldReceive('first')->once()->andReturn((object) [
            'id_respuesta' => 'CONSTANCIA-PRUEBA',
            'estatus' => $status,
            'instancia_otro' => $observation,
            'curp' => 'CURP-DE-PRUEBA',
            'nombre_curso' => 'CURSO DE PRUEBA',
            'id_puesto' => '1',
        ]);

        $userQuery = Mockery::mock(Builder::class);
        DB::shouldReceive('table')->with('administracion.users as u')->andReturn($userQuery);
        $userQuery->shouldReceive('leftJoin', 'where', 'select', 'addSelect')->andReturnSelf();
        $userQuery->shouldReceive('first')->andReturn((object) []);

        $roles = Mockery::mock(Builder::class);
        DB::shouldReceive('table')->with('administracion.user_roles as ur')->andReturn($roles);
        $roles->shouldReceive('join', 'where')->andReturnSelf();
        $roles->shouldReceive('pluck')->with('r.code')->andReturn(collect(['admin_oc']));

        $tables = Mockery::mock(Builder::class);
        DB::shouldReceive('table')->with('information_schema.tables')->andReturn($tables);
        $tables->shouldReceive('where')->andReturnSelf();
        $tables->shouldReceive('exists')->andReturn(false);

        $columns = Mockery::mock(Builder::class);
        DB::shouldReceive('table')->with('information_schema.columns')->andReturn($columns);
        $columns->shouldReceive('where', 'orderBy')->andReturnSelf();
        $columns->shouldReceive('pluck')->with('column_name')->andReturn(collect([]));
    }

    private function expectExistingCatalogValidation(): void
    {
        // Detenerse en una validación existente permite verificar la continuación sin escribir PAC.
        $catalog = Mockery::mock(Builder::class);
        DB::shouldReceive('table')->with('public.a1_cat_acciones')->once()->andReturn($catalog);
        $catalog->shouldReceive('select', 'whereRaw')->andReturnSelf();
        $catalog->shouldReceive('first')->once()->andReturn(null);
    }
}
