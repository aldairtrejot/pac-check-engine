<?php

namespace App\Models\Log;

use Illuminate\Database\Eloquent\Model;

class LogEventoUsuarioModel extends Model
{
    protected $table = 'log.log_eventos_usuario';
    protected $primaryKey = 'id_log_evento';

    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'modulo',
        'accion',
        'descripcion',
        'id_usuario',
        'id_referencia',
        'payload',
        'creado_en',
    ];

    protected $casts = [
        'payload' => 'array',
        'creado_en' => 'datetime',
    ];
}