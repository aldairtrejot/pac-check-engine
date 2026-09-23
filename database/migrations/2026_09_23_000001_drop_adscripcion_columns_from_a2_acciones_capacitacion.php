<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->capacitacionTableExists()) {
            return;
        }

        DB::statement("
            ALTER TABLE public.a2_acciones_capacitacion
            DROP COLUMN IF EXISTS adscripcion_compl
        ");

        DB::statement("
            ALTER TABLE public.a2_acciones_capacitacion
            DROP COLUMN IF EXISTS adscripcion
        ");

        DB::statement("
            ALTER TABLE public.a2_acciones_capacitacion
            DROP COLUMN IF EXISTS id_adscripcion
        ");
    }

    public function down(): void
    {
        /*
         * No se recrean estas columnas. El modulo resuelve Adscripcion desde
         * public.cat_adscripcion usando num_cursos como referencia.
         */
    }

    private function capacitacionTableExists(): bool
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', 'public')
            ->where('table_name', 'a2_acciones_capacitacion')
            ->exists();
    }
};
