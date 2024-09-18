<?php

namespace App\Notifications;

use App\Broadcasting\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendNotification extends Notification
{
    use Queueable;

    protected $totalDebt;

    public function __construct($totalDebt)
    {
        $this->totalDebt = $totalDebt;
    }

    public function via($notifiable)
    {
        return ['database', SmsChannel::class]; // Utilise le canal SMS
    }

    public function toSms($notifiable)
    {
        // app(SmsService::class)->sendSms($this->tota, $this->message);
        return "Vous avez un total de dette  de " . $this->totalDebt . " non réglée. Merci de régulariser.";
    }

    public function toDatabase($notifiable)
    {
        return [
            'client_id' => $notifiable->id,
            'message' => "Vous avez une dette de " . $this->totalDebt . " non réglée.",
            'montant_total' => $this->totalDebt,
        ];
    }
}
