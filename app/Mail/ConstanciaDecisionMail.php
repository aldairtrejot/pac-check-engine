<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConstanciaDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $nombrePersona;
    public string $nombreCurso;
    public string $folio;
    public string $fechaHora;
    public string $decision;
    public ?string $motivo;

    /**
     * Historial de capacitación del trabajador.
     *
     * Se usa en:
     * resources/views/emails/constancias/decision.blade.php
     */
    public array $historialCapacitacion;

    public function __construct(
        string $nombrePersona,
        string $nombreCurso,
        string $folio,
        string $fechaHora,
        string $decision,
        ?string $motivo = null,
        array $historialCapacitacion = []
    ) {
        $this->nombrePersona = trim($nombrePersona) !== '' ? trim($nombrePersona) : 'Usuario';
        $this->nombreCurso = trim($nombreCurso) !== '' ? trim($nombreCurso) : 'No especificado';
        $this->folio = trim($folio) !== '' ? trim($folio) : 'S/F';
        $this->fechaHora = trim($fechaHora) !== '' ? trim($fechaHora) : 'No especificada';
        $this->decision = strtoupper(trim($decision));
        $this->motivo = $motivo;
        $this->historialCapacitacion = $historialCapacitacion;
    }

    public function build()
    {
        $esAceptado = in_array($this->decision, ['ACEPTADO', 'ACEPTADA'], true);

        $subject = $esAceptado
            ? 'Constancia aceptada - ' . $this->nombreCurso . ' - Folio ' . $this->folio
            : 'Constancia rechazada - ' . $this->nombreCurso . ' - Folio ' . $this->folio;

        $subject = str_replace(["\r", "\n"], ' ', $subject);

        return $this
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($subject)
            ->view('emails.constancias.decision')
            ->with([
                'nombrePersona' => $this->nombrePersona,
                'nombreCurso' => $this->nombreCurso,
                'folio' => $this->folio,
                'fechaHora' => $this->fechaHora,
                'decision' => $this->decision,
                'motivo' => $this->motivo,
                'historialCapacitacion' => $this->historialCapacitacion,
            ]);
    }
}
