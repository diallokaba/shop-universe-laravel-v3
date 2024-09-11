<?php

namespace App\Facades;
use Illuminate\Support\Facades\Facade;

class SendSMSWithInfoBipFacade extends Facade{

    protected static function getFacadeAccessor()
    {
        return 'SendSMSWithInfoBip';
    }
}