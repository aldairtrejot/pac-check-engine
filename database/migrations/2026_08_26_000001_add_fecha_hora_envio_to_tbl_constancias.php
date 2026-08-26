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

        DB::statement("
            ALTER TABLE public.tbl_constancias
            ADD COLUMN IF NOT EXISTS fecha_hora_envio TIMESTAMP WITH TIME ZONE
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_tbl_constancias_curp_curso_estatus_fecha
            ON public.tbl_constancias USING btree
            (curp ASC NULLS LAST, nombre_curso ASC NULLS LAST, estatus ASC NULLS LAST, fecha_hora_envio ASC NULLS LAST)
        ");
    }

    public function down(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        DB::statement("DROP INDEX IF EXISTS public.idx_tbl_constancias_curp_curso_estatus_fecha");
        DB::statement("ALTER TABLE public.tbl_constancias DROP COLUMN IF EXISTS fecha_hora_envio");
    }

    private function tableExists(): bool
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', 'public')
            ->where('table_name', 'tbl_constancias')
            ->exists();
    }
};
