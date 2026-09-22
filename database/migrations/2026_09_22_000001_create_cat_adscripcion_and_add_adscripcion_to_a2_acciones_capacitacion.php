<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE IF NOT EXISTS public.cat_adscripcion
            (
                id_unidad integer,
                nombre_unidad character varying(200) COLLATE pg_catalog.\"default\",
                id_coordinacion integer,
                nombre_coordinacion character varying(250) COLLATE pg_catalog.\"default\",
                id_adscripcion integer,
                adscripcion text COLLATE pg_catalog.\"default\",
                adscripcion_compl text COLLATE pg_catalog.\"default\"
            )
            TABLESPACE pg_default
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_cat_adscripcion_id_adscripcion
            ON public.cat_adscripcion USING btree (id_adscripcion)
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_cat_adscripcion_unidad_coordinacion
            ON public.cat_adscripcion USING btree (id_unidad, id_coordinacion)
        ");

        if (! $this->capacitacionTableExists()) {
            return;
        }

        DB::statement("
            ALTER TABLE public.a2_acciones_capacitacion
            ADD COLUMN IF NOT EXISTS id_adscripcion integer
        ");

        DB::statement("
            ALTER TABLE public.a2_acciones_capacitacion
            ADD COLUMN IF NOT EXISTS adscripcion text
        ");

        DB::statement("
            ALTER TABLE public.a2_acciones_capacitacion
            ADD COLUMN IF NOT EXISTS adscripcion_compl text
        ");
    }

    public function down(): void
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

        /*
         * No se elimina public.cat_adscripcion en rollback. Puede ser un catalogo
         * oficial ya existente y borrarlo implicaria perdida de datos maestros.
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
