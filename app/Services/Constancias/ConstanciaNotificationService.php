<?php

namespace App\Services\Constancias;

use App\Mail\ConstanciaDecisionMail;
use App\Support\UserActionLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ConstanciaNotificationService
{
    public function sendDecisionEmail(
        string $correoDestinatario,
        string $nombrePersona,
        string $nombreCurso,
        string $folio,
        string $fechaHora,
        string $tipo,
        ?string $motivo = null,
        string $curp = '',
        array $historialCapacitacion = [],
        ?int $actorUserId = null
    ): array {
        $startedAt = microtime(true);
        $correoDestinatario = trim($correoDestinatario);
        $tipo = strtolower(trim($tipo));
        $mailer = (string) config('mail.default', 'log');
        $ccList = $this->ccListForType($tipo);

        $basePayload = [
            'folio' => $folio,
            'curp' => $curp,
            'tipo' => $tipo,
            'destinatario' => $correoDestinatario,
            'cc_count' => count($ccList),
            'mailer' => $mailer,
        ];

        if ($correoDestinatario === '' || ! filter_var($correoDestinatario, FILTER_VALIDATE_EMAIL)) {
            $durationMs = $this->durationMs($startedAt);

            Log::warning('No se encontró correo válido para notificación de constancia.', [
                ...$basePayload,
                'duration_ms' => $durationMs,
            ]);

            UserActionLogger::write(
                idUsuario: $actorUserId,
                modulo: 'CONSTANCIAS',
                accion: 'CORREO_CONSTANCIA_NO_ENVIADO',
                descripcion: 'No se envió la notificación porque el destinatario no es válido.',
                idReferencia: $folio,
                payload: [
                    ...$basePayload,
                    'motivo_falla' => 'destinatario_invalido',
                    'duration_ms' => $durationMs,
                ]
            );

            return [
                'sent' => false,
                'reason' => 'destinatario_invalido',
                'duration_ms' => $durationMs,
            ];
        }

        if (in_array($mailer, ['log', 'array'], true)) {
            Log::warning('El mailer activo no entrega correos reales.', [
                ...$basePayload,
                'duration_ms' => $this->durationMs($startedAt),
            ]);
        }

        try {
            retry(3, function () use (
                $correoDestinatario,
                $nombrePersona,
                $nombreCurso,
                $folio,
                $fechaHora,
                $tipo,
                $motivo,
                $historialCapacitacion,
                $ccList
            ) {
                $decision = match ($tipo) {
                    'rechazo'   => 'RECHAZADO',
                    'duplicada' => 'DUPLICADA',
                    default     => 'ACEPTADO',
                };

                $mail = Mail::to($correoDestinatario);

                if (! empty($ccList)) {
                    $mail->cc($ccList);
                }

                $mail->send(new ConstanciaDecisionMail(
                    nombrePersona: $nombrePersona,
                    nombreCurso: $nombreCurso,
                    folio: $folio,
                    fechaHora: $fechaHora,
                    decision: $decision,
                    motivo: $motivo,
                    historialCapacitacion: $historialCapacitacion
                ));
            }, 250);

            $durationMs = $this->durationMs($startedAt);
            $entregaReal = ! in_array($mailer, ['log', 'array'], true);

            Log::info('Notificación de constancia procesada.', [
                ...$basePayload,
                'duration_ms' => $durationMs,
                'entrega_real' => $entregaReal,
            ]);

            UserActionLogger::write(
                idUsuario: $actorUserId,
                modulo: 'CONSTANCIAS',
                accion: $entregaReal ? 'CORREO_CONSTANCIA_ENVIADO' : 'CORREO_CONSTANCIA_SIMULADO',
                descripcion: $entregaReal
                    ? 'Notificación de constancia enviada por el mailer configurado.'
                    : 'Notificación de constancia procesada por un mailer que no entrega correos reales.',
                idReferencia: $folio,
                payload: [
                    ...$basePayload,
                    'duration_ms' => $durationMs,
                    'entrega_real' => $entregaReal,
                ]
            );

            return [
                'sent' => $entregaReal,
                'reason' => $entregaReal ? null : 'mailer_no_entrega',
                'duration_ms' => $durationMs,
                'mailer' => $mailer,
            ];
        } catch (\Throwable $e) {
            $durationMs = $this->durationMs($startedAt);

            Log::error('Error enviando correo de constancia: ' . $e->getMessage(), [
                ...$basePayload,
                'duration_ms' => $durationMs,
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            UserActionLogger::write(
                idUsuario: $actorUserId,
                modulo: 'CONSTANCIAS',
                accion: 'CORREO_CONSTANCIA_ERROR',
                descripcion: 'Falló el envío de la notificación de constancia.',
                idReferencia: $folio,
                payload: [
                    ...$basePayload,
                    'duration_ms' => $durationMs,
                    'exception' => get_class($e),
                    'error' => $e->getMessage(),
                ]
            );

            return [
                'sent' => false,
                'reason' => 'exception',
                'duration_ms' => $durationMs,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function ccListForType(string $tipo): array
    {
        $raw = match ($tipo) {
            'rechazo'   => trim((string) env('CONSTANCIAS_RECHAZO_CC', '')),
            'duplicada' => trim((string) env('CONSTANCIAS_DUPLICADA_CC', env('CONSTANCIAS_RECHAZO_CC', ''))),
            default     => trim((string) env('CONSTANCIAS_ACEPTACION_CC', '')),
        };

        if ($raw === '') {
            return [];
        }

        $emails = preg_split('/[;,]+/', $raw) ?: [];

        return collect($emails)
            ->map(fn ($email) => trim((string) $email))
            ->filter(fn ($email) => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }

    private function durationMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }
}
