<?php

namespace App\Services;

use MongoDB\Client;

class MongoClientImpl implements MongoClientInterface{

    protected $client;

    public function __construct(){

        $this->client = new Client('mongodb+srv://diallo:Passer123@cluster0.dh8zvpu.mongodb.net/shop-universe-laravel-archive?retryWrites=true&w=majority&appName=Cluster0');
    }
    public function getClient()
    {
        return $this->client;
    }
}