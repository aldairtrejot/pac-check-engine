<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        // RFC debe poder quedar NULL cuando aún no se conoce.
        if ($this->columnExists('rfc')) {
            DB::statement("
                ALTER TABLE public.a2_acciones_capacitacion
                ALTER COLUMN rfc DROP NOT NULL
            ");
        }

        // Campo solicitado para el alta de empleados.
        DB::statement("
            ALTER TABLE public.a2_acciones_capacitacion
            ADD COLUMN IF NOT EXISTS observaciones_plantilla TEXT
        ");
    }

    public function down(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        DB::statement("
            ALTER TABLE public.a2_acciones_capacitacion
            DROP COLUMN IF EXISTS observaciones_plantilla
        ");

        /*
         * No se vuelve a colocar NOT NULL en RFC de forma automática.
         * Después de habilitar RFC opcional pueden existir registros con RFC NULL,
         * por lo que restaurar NOT NULL podría romper el rollback.
         */
    }

    private function tableExists(): bool
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', 'public')
            ->where('table_name', 'a2_acciones_capacitacion')
            ->exists();
    }

    private function columnExists(string $column): bool
    {
        return DB::table('information_schema.columns')
            ->where('table_schema', 'public')
            ->where('table_name', 'a2_acciones_capacitacion')
            ->where('column_name', $column)
            ->exists();
    }
};
