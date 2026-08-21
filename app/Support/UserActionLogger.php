<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserActionLogger
{
    private static ?bool $tableExists = null;
    private static ?array $columns = null;

    public static function write(
        ?int $idUsuario,
        string $modulo,
        string $accion,
        ?string $descripcion = null,
        $idReferencia = null,
        ?array $payload = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Request $request = null
    ): void {
        $request = $request ?: request();
        $payload = self::buildPayload($payload, $oldValues, $newValues, $request);

        try {
            if (! self::tableAvailable()) {
                Log::info('AUDITORIA_USUARIO', [
                    'modulo'        => $modulo,
                    'accion'        => $accion,
                    'descripcion'   => $descripcion,
                    'id_usuario'    => $idUsuario,
                    'id_referencia' => $idReferencia,
                    'payload'       => $payload,
                ]);

                return;
            }

            $row = [
                'modulo'        => $modulo,
                'accion'        => $accion,
                'descripcion'   => $descripcion,
                'id_usuario'    => $idUsuario,
                'id_referencia' => $idReferencia !== null ? (string) $idReferencia : null,
                'payload'       => self::encodeJson($payload),
                'creado_en'     => now(),
            ];

            self::addIfColumnExists($row, 'ip', $payload['origen']['ip'] ?? null);
            self::addIfColumnExists($row, 'ip_address', $payload['origen']['ip'] ?? null);
            self::addIfColumnExists($row, 'user_agent', $payload['origen']['user_agent'] ?? null);
            self::addIfColumnExists($row, 'url', $payload['origen']['url'] ?? null);
            self::addIfColumnExists($row, 'method', $payload['origen']['method'] ?? null);
            self::addIfColumnExists($row, 'http_method', $payload['origen']['method'] ?? null);
            self::addIfColumnExists($row, 'valores_anteriores', self::encodeJson($oldValues));
            self::addIfColumnExists($row, 'valores_nuevos', self::encodeJson($newValues));

            $columns = array_flip(self::columns());

            $row = array_filter(
                $row,
                fn ($value, $key) => isset($columns[$key]),
                ARRAY_FILTER_USE_BOTH
            );

            if ($row === []) {
                Log::warning('No hay columnas compatibles para guardar auditoría.', [
                    'modulo' => $modulo,
                    'accion' => $accion,
                ]);

                return;
            }

            DB::table('log.log_eventos_usuario')->insert($row);
        } catch (\Throwable $e) {
            Log::error('No se pudo guardar log_eventos_usuario', [
                'message' => $e->getMessage(),
                'modulo'  => $modulo,
                'accion'  => $accion,
            ]);
        }
    }

    public static function fromRequest(
        Request $request,
        ?int $idUsuario,
        string $modulo,
        string $accion,
        ?string $descripcion = null,
        $idReferencia = null,
        ?array $payload = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        self::write(
            idUsuario: $idUsuario,
            modulo: $modulo,
            accion: $accion,
            descripcion: $descripcion,
            idReferencia: $idReferencia,
            payload: $payload,
            oldValues: $oldValues,
            newValues: $newValues,
            request: $request
        );
    }

    private static function tableAvailable(): bool
    {
        if (self::$tableExists !== null) {
            return self::$tableExists;
        }

        try {
            self::$tableExists = DB::table('information_schema.tables')
                ->where('table_schema', 'log')
                ->where('table_name', 'log_eventos_usuario')
                ->exists();
        } catch (\Throwable $e) {
            self::$tableExists = false;
        }

        return self::$tableExists;
    }

    private static function columns(): array
    {
        if (self::$columns !== null) {
            return self::$columns;
        }

        try {
            self::$columns = DB::table('information_schema.columns')
                ->where('table_schema', 'log')
                ->where('table_name', 'log_eventos_usuario')
                ->pluck('column_name')
                ->map(fn ($column) => (string) $column)
                ->all();
        } catch (\Throwable $e) {
            self::$columns = [];
        }

        return self::$columns;
    }

    private static function addIfColumnExists(array &$row, string $column, $value): void
    {
        if (in_array($column, self::columns(), true)) {
            $row[$column] = $value;
        }
    }

    private static function buildPayload(
        ?array $payload,
        ?array $oldValues,
        ?array $newValues,
        ?Request $request
    ): array {
        $payload = self::maskSensitive($payload ?? []);

        if ($oldValues !== null) {
            $payload['valores_anteriores'] = self::maskSensitive($oldValues);
        }

        if ($newValues !== null) {
            $payload['valores_nuevos'] = self::maskSensitive($newValues);
        }

        if ($request) {
            $payload['origen'] = [
                'ip'         => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'method'     => $request->method(),
                'url'        => $request->fullUrl(),
            ];
        }

        return $payload;
    }

    private static function maskSensitive(array $data): array
    {
        $masked = [];

        foreach ($data as $key => $value) {
            $keyString = (string) $key;

            if (preg_match('/password|contrasena|contraseña|token|secret|captcha|remember/i', $keyString)) {
                $masked[$key] = '[redacted]';
                continue;
            }

            $masked[$key] = is_array($value)
                ? self::maskSensitive($value)
                : $value;
        }

        return $masked;
    }

    private static function encodeJson($value): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
