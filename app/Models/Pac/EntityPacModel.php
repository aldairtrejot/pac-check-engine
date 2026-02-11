<?php

namespace App\Models\Pac;

use Illuminate\Database\Eloquent\Model;

class EntityPacModel extends Model
{
    // table
    protected $table = 'public.a2_acciones_empleados';

    // primary field
    protected $primaryKey = 'id_empl_accion';

    // incremental status
    public $incrementing = true;

    // primary field type
    protected $keyType = 'int';

    // create and update fields
    public $timestamps = false;

    // fields
    protected $fillable = [
        'id_empl_accion',
        'id_puesto',
        'curp',
        'id_accion',
        'id_finalidad',
        'horas_real',
        'id_instancia',
        'costo_unitario',
        'fecha_ini',
        'fecha_fin',
        'id_trimestre',
        'id_num_curso',
        'eval_aprendizaje',
        'observaciones',
        'id_cat_estatus',
        'id_cat_tematica',
        'horas_progamadas',

        // ✅ NUEVO
        'calificacion',
    ];
}