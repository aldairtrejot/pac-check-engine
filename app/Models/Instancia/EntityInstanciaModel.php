<?php

namespace App\Models\Instancia;

use Illuminate\Database\Eloquent\Model;

class EntityInstanciaModel extends Model
{
    // Tabla
    protected $table = 'public.cat_instancias';

    // PK
    protected $primaryKey = 'id_instancia';
    public $incrementing = false;
    protected $keyType = 'string';

    // Sin timestamps
    public $timestamps = false;

    // Campos asignables
    protected $fillable = [
        'id_instancia',
        'ramo',
        'ur',
        'consecutivo',
        'instancia',
        'anio',
        'estatus',
    ];
}
