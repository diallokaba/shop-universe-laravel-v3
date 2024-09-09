<?php

namespace App\Providers;

use App\Events\ClientEvent;
use App\Events\UserEvent;
use App\Listeners\SendMailListener;
use App\Listeners\UploadPhotoListener;
use App\Models\Client;
use App\Models\User;
use App\Observers\ClientObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        ClientEvent::class => [
            UploadPhotoListener::class,
            SendMailListener::class,
        ],

        /*UserEvent::class => [
            UploadPhotoListener::class,
            SendMailListener::class,
        ],*/
    ];

    /**
     * Register any events for your application.
     */
    /*public function boot(): void
    {
        Client::observe(ClientObserver::class);
        User::observe(UserObserver::class);
    }*/

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
