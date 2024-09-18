<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendNotificationForSettledDebt extends Notification implements ShouldQueue
{
    use Queueable;

    private $totalDette, $nomClient, $prenomClient;
    private $message = null;

    /**
     * Create a new notification instance.
     */
    public function __construct($totalDette, $nomClient, $prenomClient, $message = null)
    {
        $this->totalDette = $totalDette;
        $this->nomClient = $nomClient;
        $this->prenomClient = $prenomClient;
        $this->message = $message ?: "Bonjour {$this->nomClient} {$this->prenomClient}, votre dette totale est de : " . number_format($this->totalDette, 0, ',', ' ') . " FCFA. Pensez à faire un règlement.";
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'client_id' => $notifiable->id,
            'nom_complet' => $this->nomClient . ' ' . $this->prenomClient,
            'total_dette' => $this->totalDette,
            'message' => $this->message
        ];
    }
}
