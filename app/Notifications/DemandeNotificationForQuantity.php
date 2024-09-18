<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DemandeNotificationForQuantity extends Notification
{
    use Queueable;

    private $articlesDisponibles;

    /**
     * Create a new notification instance.
     */
    public function __construct($articlesDisponibles)
    {
        $this->articlesDisponibles = $articlesDisponibles;
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
            'message' => 'Certains articles de votre demande peuvent ne pas être disponibles en quantité suffisante.',
            'articles_disponibles' => $this->articlesDisponibles
        ];
    }
}
