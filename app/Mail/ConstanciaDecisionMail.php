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

    public function __construct(
        string $nombrePersona,
        string $nombreCurso,
        string $folio,
        string $fechaHora,
        string $decision,
        ?string $motivo = null
    ) {
        $this->nombrePersona = trim($nombrePersona) !== '' ? trim($nombrePersona) : 'Usuario';
        $this->nombreCurso = trim($nombreCurso) !== '' ? trim($nombreCurso) : 'No especificado';
        $this->folio = trim($folio) !== '' ? trim($folio) : 'S/F';
        $this->fechaHora = trim($fechaHora);
        $this->decision = strtoupper(trim($decision));
        $this->motivo = $motivo;
    }

    public function build()
    {
        $esAceptado = $this->decision === 'ACEPTADO';

        $subject = $esAceptado
            ? 'Constancia aceptada - ' . $this->nombreCurso . ' - Folio ' . $this->folio
            : 'Constancia rechazada - ' . $this->nombreCurso . ' - Folio ' . $this->folio;

        $subject = str_replace(["\r", "\n"], ' ', $subject);

        return $this
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($subject)
            ->view('emails.constancias.decision');
    }
}