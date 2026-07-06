<?php

namespace App\Models\Pac\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GetTrimestreModel extends Model
{
    /**
     * Obtiene el id_trimestre usando solo mes y día.
     *
     * Esto permite que cat_trimestres tenga rangos base como:
     * 2025-01-01 a 2025-03-31
     * 2025-04-01 a 2025-06-30
     * 2025-07-01 a 2025-09-30
     * 2025-10-01 a 2025-12-31
     *
     * Y que funcione también para fechas de constancias 2026, 2027, etc.
     */
    public function getTrimestre($date)
    {
        if (empty($date)) {
            return null;
        }

        $date = trim((string) $date);

        if ($date === '') {
            return null;
        }

        try {
            /*
            |--------------------------------------------------------------------------
            | Comparación por mes-día
            |--------------------------------------------------------------------------
            | Ejemplo:
            | - fecha de constancia: 2026-04-14
            | - se compara como: 04-14
            | - contra cat_trimestres: 04-01 a 06-30
            */
            $result = DB::table('public.cat_trimestres')
                ->select('id_trimestre')
                ->whereRaw(
                    "TO_CHAR(?::date, 'MM-DD') BETWEEN TO_CHAR(fecha_inicio, 'MM-DD') AND TO_CHAR(fecha_fin, 'MM-DD')",
                    [$date]
                )
                ->first();

            return $result ? (int) $result->id_trimestre : null;

        } catch (\Throwable $e) {
            return null;
        }
    }
}