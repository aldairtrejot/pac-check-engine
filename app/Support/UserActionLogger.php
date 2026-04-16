<?php

namespace App\Support;

use App\Models\Log\LogEventoUsuarioModel;
use Illuminate\Support\Facades\Log;

class UserActionLogger
{
    public static function write(
        int $idUsuario,
        string $modulo,
        string $accion,
        ?string $descripcion = null,
        $idReferencia = null,
        ?array $payload = null
    ): void {
        try {
            LogEventoUsuarioModel::create([
                'modulo'        => $modulo,
                'accion'        => $accion,
                'descripcion'   => $descripcion,
                'id_usuario'    => $idUsuario,
                'id_referencia' => $idReferencia !== null ? (string) $idReferencia : null,
                'payload'       => $payload,
                'creado_en'     => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('No se pudo guardar log_eventos_usuario', [
                'message' => $e->getMessage(),
                'modulo'  => $modulo,
                'accion'  => $accion,
            ]);
        }
    }
}