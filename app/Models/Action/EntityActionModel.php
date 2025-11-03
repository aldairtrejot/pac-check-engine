<?php

namespace App\Models\Action;

use Illuminate\Database\Eloquent\Model;

class EntityActionModel extends Model
{
    protected $table = 'public.a1_cat_acciones';
    protected $primaryKey = 'id_accion';

    public $incrementing = false;   // 👉 lo controlamos nosotros
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'id_accion',        // 👉 lo vamos a llenar a mano
        'ramo',
        'ur',
        'institucion',
        'nombre_accion',
        'duracion_hrs',
        'tipo_capacitacion',
        'modalidad',
        'estatus',
        'tematica',
        'finalidad',
    ];
}
