<?php

namespace App\Broadcasting;

use App\Models\User;
use App\Notifications\SendNotification;
use App\Services\SendSMSWithInfoBip;

class SmsChannel
{
    protected $smsService;

    public function __construct()
    {
        $this->smsService = new SendSMSWithInfoBip();
    }
    
    public function send($notifiable, SendNotification $notification)
    {
        // Vérifier si le modèle peut recevoir des notifications par SMS
        if (!$notifiable->routeNotificationFor('sms')) {
            return;
        }

        // Récupérer le message depuis la notification
        $message = $notification->toSms($notifiable);


        // Envoyer le SMS en utilisant le service choisi (Twilio, InfoBip, etc.)
        $this->smsService->sendSms($notifiable->routeNotificationFor('sms'), $message);
    }
}
