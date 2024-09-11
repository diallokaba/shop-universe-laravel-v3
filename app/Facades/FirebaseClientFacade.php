<?php

namespace App\Facades;
use Illuminate\Support\Facades\Facade;

class FirebaseClientFacade extends Facade{

    protected static function getFacadeAccessor()
    {
        return 'firebaseClient';
    }
}