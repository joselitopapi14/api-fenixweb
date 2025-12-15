<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificacionLiderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $lider;
    public $cantidadNovedades;
    public $novedades;

    /**
     * Create a new message instance.
     */
    public function __construct($lider, $cantidadNovedades, $novedades)
    {
        $this->lider = $lider;
        $this->cantidadNovedades = $cantidadNovedades;
        $this->novedades = $novedades;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔔 Novedades Pendientes - Sistema VoteManager',
            from: config('services.mailjet.from_email'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notificacion-lider',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
