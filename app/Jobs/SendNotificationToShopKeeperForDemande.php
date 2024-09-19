<?php

namespace App\Jobs;

use App\Models\Client;
use App\Notifications\DemandeNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationToShopKeeperForDemande implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $boutiquiers = Client::with('user')->whereHas('user', function($query) {
            $query->where('role_id', 2);
        })->get();
        $message = "Une demande de dette vient d'être soumise";
        foreach ($boutiquiers as $boutiquier) {
            $boutiquier->notify(new DemandeNotification($message));
        }
    }
}
