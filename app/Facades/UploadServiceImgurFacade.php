<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class UploadServiceImgurFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'uploadServiceImgur';
    }
}