<?php

namespace App\Listeners;

use App\Events\ClientEvent;
use App\Events\UserEvent;
use App\Jobs\SendMailJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMailListener
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
        SendMailJob::dispatch($event->user, $event->client);
    }
}
