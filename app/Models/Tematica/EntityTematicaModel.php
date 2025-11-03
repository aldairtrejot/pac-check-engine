<?php

namespace App\Models\Tematica;

use Illuminate\Database\Eloquent\Model;

class EntityTematicaModel extends Model
{
    protected $table = 'public.cat_tematica';
    protected $primaryKey = 'id_tematica';

    public $incrementing = false;      // PK es TEXT, no autoincrement
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id_tematica',
        'consecutivo',
        'ramo',
        'ur',
        'tematica',
        'categorias',
        'enfoque',
    ];
}
