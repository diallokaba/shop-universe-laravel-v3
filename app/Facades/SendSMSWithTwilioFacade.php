<?php

namespace App\Facades;
use Illuminate\Support\Facades\Facade;

class SendSMSWithTwilioFacade extends Facade{

    protected static function getFacadeAccessor()
    {
        return 'SendSMSWithTwilio';
    }
}