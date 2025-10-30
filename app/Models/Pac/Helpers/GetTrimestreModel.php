<?php

namespace App\Models\Pac\Helpers;
use Illuminate\Support\Facades\DB;  
use Illuminate\Database\Eloquent\Model;

class GetTrimestreModel extends Model
{
    /**
     * Summary of getTrimestre
     * @param mixed $date
     */
    public function getTrimestre($date)
        {
            $result = DB::table('public.cat_trimestres')
                ->select('public.cat_trimestres.id_trimestre')
                ->whereRaw('? BETWEEN public.cat_trimestres.fecha_inicio AND public.cat_trimestres.fecha_fin', [$date])
                ->first();

            return $result ? $result->id_trimestre : null;
        }
    }
