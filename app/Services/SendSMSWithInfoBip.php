<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;

class SendSMSWithInfoBip {

    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('services.infobip.base_url'),  
            'headers' => [
                'Authorization' => 'App ' . config('services.infobip.api_key'),  
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);
    }

    public function sendSms(string $message, string $phoneNumber)
    {
        $body = [
            "messages" => [
                [
                    "destinations" => [
                        ["to" => $phoneNumber]
                    ],
                    "from" => "Shop-Universe",
                    "text" => $message
                ]
            ]
        ];

        try {
            $response = $this->client->post('/sms/2/text/advanced', [
                'json' => $body
            ]);

            if ($response->getStatusCode() == 200) {
                // Traite la réponse en cas de succès
                echo $response->getBody();
            } else {
                // Gère le cas où le statut HTTP est inattendu
                echo 'Unexpected HTTP status: ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase();
            }
        } catch (Exception $e) {
            // Capture et affiche l'exception
            echo 'Error: ' . $e->getMessage();
        }
    }
}
