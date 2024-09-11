<?php

namespace App\Jobs;

use App\Models\Dette;
use App\Services\SendSMSWithInfoBip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

//use App\Facades\SendSMSWithInfoBipFacade as sendSMSWithInfoBip;
//use App\Services\SendSMSWithInfoBip as ServicesSendSMSWithInfoBip;
use Exception;

class SendSMSNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $dettes = Dette::with(['client', 'paiements'])->where('statut', 'nonsolde')->get();

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
                $smsService = new SendSMSWithInfoBip();
                $message = "Bonjour " . $dette['nom'] . " " . $dette['prenom'] . ", votre dette totale non soldée est de : " . number_format($dette['montant_total'], 0, ',', ' ') . " FCFA.";
                $smsService->sendSms($message, $dette['telephone']);
            }

        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}
