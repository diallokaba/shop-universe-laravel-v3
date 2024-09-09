<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class UploadServiceImgur{

    protected $clientId;
    public function __construct(){
        $this->clientId = env('IMGUR_CLIENT_ID');
    }

    public function uploadImageWithImgur($imagePath)
    {

        /*try{
            $client = new Client();
            $response = $client->request('POST', 'https://api.imgur.com/3/image', [
                'headers' => [
                    'Authorization' => 'Client-ID ' . $this->clientId,
                ],
                'multipart' => [
                    [
                        'name' => 'image',
                        'contents' => fopen($imagePath, 'r'),
                    ]
                ],
            ]);
            $body = json_decode((string)$response->getBody());
    
            if ($body && $body->success) {
                return $body->data->link ?? null;
            } else {
                throw new Exception('Error from Imgur API: ' . json_encode($body->data->error ?? 'Unknown error'));
            }
        }catch(Exception $e){
            Log::error('Imgur upload error: ' . $e->getMessage(), ['imagePath' => $imagePath]);
            throw new Exception('Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
        }*/

        
        $client = new Client();
        $response = $client->request('POST', 'https://api.imgur.com/3/image', [
            'headers' => [
                'Authorization' => 'Client-ID ' . $this->clientId,
            ],
            'multipart' => [
                [
                    'name' => 'image',
                    'contents' => fopen($imagePath, 'r'),
                ]
            ],
        ]);
        $body = json_decode((string)$response->getBody());

        if ($body && $body->success) {
            return $body->data->link ?? null;
        } else {
            return null;
        }
    }
}