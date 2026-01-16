<?php

namespace App\Models\Action;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class EntityActionModel extends User
{
    protected $table = 'public.a1_cat_acciones';
    protected $primaryKey = 'id_accion';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_accion',
        'ramo',
        'ur',
        'institucion',
        'nombre_accion',
        'duracion_hrs',
        'tipo_capacitacion',
        'modalidad',
        'estatus',
        'tematica',
        'finalidad', // 👈 IMPORTANTE: aquí va el texto de la finalidad
    ];
}
