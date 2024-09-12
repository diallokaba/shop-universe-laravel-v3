<?php

namespace App\Services;

use Twilio\Rest\Client;

class SendSMSServiceWithTwilio
{
    protected $client;
    protected $from;

    public function __construct()
    {
        $this->client = new Client(config('services.twilio.account_sid'), config('services.twilio.auth_token'));
        $this->from = config('services.twilio.from');
    }

    public function sendSms($to, $message)
    {
        return $this->client->messages->create(
            $to,
            [
                'from' => $this->from,
                'body' => $message
            ]
        );
    }
}