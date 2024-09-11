<?php

namespace App\Facades;
use Illuminate\Support\Facades\Facade;

class MongoClientFacade extends Facade{

    protected static function getFacadeAccessor()
    {
        return 'mongoClient';
    }
}