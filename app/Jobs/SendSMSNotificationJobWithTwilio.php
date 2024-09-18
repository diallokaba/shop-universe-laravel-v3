<?php

namespace App\Jobs;

use App\Models\Dette;
use App\Services\SendSMSServiceWithTwilio;
use Exception;
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
        $this->twilioService = new SendSMSServiceWithTwilio();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        echo 'Send SMS Notification Job started at ' . now() . ' with twilio';
        try {
            $dettes = Dette::with(['client', 'paiements'])->where('statut', 'nonsolde')->get();

            if(!empty($dettes)) {
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
                            'nom' => $dette->client->nom,
                            'prenom' => $dette->client->prenom,
                            'telephone' => $dette->client->telephone
                        ];
                    }
                }

                // Envoie le SMS à chaque client avec la somme totale des dettes non soldées
                foreach ($detteClientNonSoldee as $clientId => $dette) {
                    $message = "Bonjour " . $dette['nom'] . " " . $dette['prenom'] . ", votre dette totale non soldée est de : " . number_format($dette['montant_total'], 0, ',', ' ') . " FCFA.";
                    $this->twilioService->sendSms($message, $dette['telephone']);
                }
            }else{
                echo 'Aucune dette non solde à notifier...';
            }

        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}
