<?php

namespace App\Jobs;

use App\Services\SendSMSWithTwilio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSMSNotificationJobWithTwilio implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $twilioService;
    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->twilioService = new SendSMSWithTwilio();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $message = "Bonjour Pape Fally votre dette est 10000 FCFA";
        $this->twilioService->sendSms("+221785222794", $message);
    }
}
