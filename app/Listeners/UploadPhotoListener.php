<?php

namespace App\Listeners;

use App\Events\ClientEvent;
use App\Events\UserEvent;
use App\Jobs\UploadPhotoJob;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UploadPhotoListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ClientEvent $event): void
    {
        UploadPhotoJob::dispatch($event->user, $event->client);   
    }
}
