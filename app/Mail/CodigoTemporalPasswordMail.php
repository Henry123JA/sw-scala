<?php

namespace App\Mail;

use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CodigoTemporalPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Usuario $usuario,
        public string $codigoTemporal,
        public int $minutosValidez = 10
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Código Temporal de Seguridad ({$this->codigoTemporal}) — Academia SCALA",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.codigo-temporal',
        );
    }
}
