<?php

namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendMailAttachment extends Mailable{
    use Queueable, SerializesModels;
    private $client;
    private $user;
    private $pdfFile;
    public function __construct($client, $user, $pdfFile){
        $this->client = $client;
        $this->user = $user;
        $this->pdfFile = $pdfFile;
    }

    public function build(){
        return $this->subject('Carte de fidélité')
            ->view('emails.fidelity_card')
            ->attach($this->pdfFile)
            ->with([
                'pseudo' => $this->user->nom . ' ' . $this->user->prenom,
                'email' => $this->user->login,
                'surnom' => $this->client->surnom
            ]);
    }

      /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Client Fidelity Card Mail',
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