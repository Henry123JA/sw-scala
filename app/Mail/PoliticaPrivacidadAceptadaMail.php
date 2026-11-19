<?php

namespace App\Mail;

use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PoliticaPrivacidadAceptadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Usuario $usuario,
        public string $fechaAceptacion
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Política de Privacidad y Protección de Datos — Academia de Música SCALA',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.politica-privacidad',
        );
    }
}
