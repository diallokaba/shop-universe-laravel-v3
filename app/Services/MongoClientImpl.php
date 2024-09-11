<?php

namespace App\Services;

use MongoDB\Client;

class MongoClientImpl implements MongoClientInterface{

    protected $client;

    public function __construct(){

        $this->client = new Client(env('MONGO_URL'));
    }
    public function getClient()
    {
        return $this->client;
    }
}