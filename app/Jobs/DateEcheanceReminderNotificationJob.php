<?php

namespace App\Jobs;

use App\Models\Dette;
use App\Notifications\SendNotificationForSettledDebt;
use App\Services\SendSMSWithInfoBip;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DateEcheanceReminderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    private $sendSmsWithInfoBip;
    public function __construct()
    {
        $this->sendSmsWithInfoBip = new SendSMSWithInfoBip();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        echo 'Send SMS Notification Job started at ' . now() . ' with infobip';
        try {
            $dettes = Dette::with(['client', 'paiements'])->where('statut', 'nonsolde')->where('echeance', '<', now())->get();

            if(($dettes->isNotEmpty())) {
                $detteClientNonSoldee = [];

                foreach ($dettes as $dette) {
                    $clientId = $dette->client->id;
    
                    // Calcule le montant total restant à payer pour chaque dette
                    $montantPaye = $dette->paiements->sum('montant');
                    $montantRestant = $dette->montant - $montantPaye;
    
                    if (isset($detteClientNonSoldee[$clientId])) {
                        // Ajoute le montant restant à la somme totale pour ce client
                        $detteClientNonSoldee[$clientId]['montant_total'] += $montantRestant;
                    } else {
                        // Ajoute le client avec les informations de dette initiales
                        $detteClientNonSoldee[$clientId] = [
                            'montant_total' => $montantRestant,
                            'client' => $dette->client
                        ];
                    }
                }

                
                // Envoie le SMS à chaque client avec la somme totale des dettes non soldées
                foreach ($detteClientNonSoldee as $clientId => $dette) {
                    $client = $dette['client'];
                    
                    //Envoie de SMS
                    $message = "Bonjour " . $client->nom . " " . $client->prenom . ", votre total de dette est de : " . number_format($dette['montant_total'], 0, ',', ' ') . " FCFA.";
                    $this->sendSmsWithInfoBip->sendSms($message, $client->telephone);

                    //Envoie de notification
                    $client->notify(new SendNotificationForSettledDebt($dette['montant_total'], $client->nom, $client->prenom));
                }
            }else{
                echo 'Aucune dette non solde à notifier...';
            }

        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}
