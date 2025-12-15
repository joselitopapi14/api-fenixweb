<?php

namespace App\Mail;

use App\Models\Factura;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class FacturaEnviada extends Mailable
{
    use Queueable, SerializesModels;

    public $factura;
    public $zipPath;
    public $asuntoPersonalizado;

    /**
     * Create a new message instance.
     */
    public function __construct(Factura $factura, $zipPath, $asuntoPersonalizado = null)
    {
        $this->factura = $factura;
        $this->zipPath = $zipPath;
        $this->asuntoPersonalizado = $asuntoPersonalizado;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $asunto = $this->asuntoPersonalizado ?: "Factura Electrónica No. {$this->factura->numero_factura}";

        return new Envelope(
            subject: $asunto,
            from: config('mail.from.address', env('MAIL_FROM_ADDRESS')),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.factura-enviada',
            with: [
                'factura' => $this->factura,
                'empresa' => $this->factura->empresa,
                'cliente' => $this->factura->cliente,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->zipPath && file_exists($this->zipPath)) {
            $attachments[] = Attachment::fromPath($this->zipPath)
                ->as("factura_{$this->factura->numero_factura}.zip")
                ->withMime('application/zip');
        }

        return $attachments;
    }

    /**
     * Build the message (método alternativo para compatibilidad)
     */
    public function build()
    {
        $asunto = $this->asuntoPersonalizado ?: "Factura Electrónica No. {$this->factura->numero_factura}";

        $mail = $this->subject($asunto)
                    ->view('emails.factura-enviada')
                    ->with([
                        'factura' => $this->factura,
                        'empresa' => $this->factura->empresa,
                        'cliente' => $this->factura->cliente,
                    ]);

        if ($this->zipPath && file_exists($this->zipPath)) {
            $mail->attach($this->zipPath, [
                'as' => "factura_{$this->factura->numero_factura}.zip",
                'mime' => 'application/zip',
            ]);
        }

        return $mail;
    }
}
