<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->tableExists('administracion', 'users')) {
            DB::statement("
                ALTER TABLE administracion.users
                ADD COLUMN IF NOT EXISTS id_adscripcion_scope integer NULL
            ");

            DB::statement("
                COMMENT ON COLUMN administracion.users.id_adscripcion_scope IS
                'Alcance opcional para administradores. NULL ve todo; con valor limita por public.cat_adscripcion.id_adscripcion / public.a2_acciones_capacitacion.num_cursos.'
            ");

            DB::statement("
                CREATE INDEX IF NOT EXISTS idx_users_id_adscripcion_scope
                ON administracion.users USING btree (id_adscripcion_scope)
                WHERE id_adscripcion_scope IS NOT NULL
            ");
        }

        $this->grantUserAdministrationPermissions();

        if ($this->columnExists('public', 'a2_acciones_capacitacion', 'num_cursos')) {
            DB::statement("
                CREATE INDEX IF NOT EXISTS idx_a2_acciones_capacitacion_num_cursos
                ON public.a2_acciones_capacitacion USING btree (num_cursos)
                WHERE num_cursos IS NOT NULL
            ");
        }

        if ($this->columnExists('public', 'tbl_constancias', 'id_adscripcion')) {
            DB::statement("
                CREATE INDEX IF NOT EXISTS idx_tbl_constancias_id_adscripcion
                ON public.tbl_constancias USING btree (id_adscripcion)
                WHERE id_adscripcion IS NOT NULL
            ");
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS administracion.idx_users_id_adscripcion_scope');
        DB::statement('DROP INDEX IF EXISTS public.idx_a2_acciones_capacitacion_num_cursos');
        DB::statement('DROP INDEX IF EXISTS public.idx_tbl_constancias_id_adscripcion');

        if ($this->tableExists('administracion', 'users')) {
            DB::statement('ALTER TABLE administracion.users DROP COLUMN IF EXISTS id_adscripcion_scope');
        }
    }

    private function tableExists(string $schema, string $table): bool
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', $schema)
            ->where('table_name', $table)
            ->exists();
    }

    private function columnExists(string $schema, string $table, string $column): bool
    {
        return DB::table('information_schema.columns')
            ->where('table_schema', $schema)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->exists();
    }

    private function grantUserAdministrationPermissions(): void
    {
        foreach (['felix.tinajero', 'ftinajero'] as $role) {
            if (! $this->roleExists($role)) {
                continue;
            }

            $grantee = $this->quoteIdentifier($role);

            if ($this->tableExists('administracion', 'users')) {
                DB::statement("
                    GRANT SELECT, INSERT, UPDATE, DELETE
                    ON TABLE administracion.users
                    TO {$grantee}
                ");
            }

            if ($this->sequenceExists('administracion', 'users_id_seq')) {
                DB::statement("
                    GRANT USAGE, SELECT
                    ON SEQUENCE administracion.users_id_seq
                    TO {$grantee}
                ");
            }

            if ($this->tableExists('administracion', 'roles')) {
                DB::statement("
                    GRANT SELECT
                    ON TABLE administracion.roles
                    TO {$grantee}
                ");
            }

            if ($this->tableExists('administracion', 'user_roles')) {
                DB::statement("
                    GRANT SELECT, INSERT, UPDATE, DELETE
                    ON TABLE administracion.user_roles
                    TO {$grantee}
                ");
            }
        }
    }

    private function roleExists(string $role): bool
    {
        return DB::table('pg_roles')
            ->where('rolname', $role)
            ->exists();
    }

    private function sequenceExists(string $schema, string $sequence): bool
    {
        return DB::table('information_schema.sequences')
            ->where('sequence_schema', $schema)
            ->where('sequence_name', $sequence)
            ->exists();
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
};
