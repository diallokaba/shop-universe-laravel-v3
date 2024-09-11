<?php

namespace App\Jobs;

use App\Facades\FirebaseClientFacade;
use App\Models\Dette;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ArchiveDetteJobWithFireBase implements ShouldQueue
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
        // Récupérer les dettes soldées de la base de données locale
        DB::beginTransaction();
        $dettes = Dette::with(['paiements', 'client'])->where('statut', 'solde')->get();

        // Définir une référence de collection pour archiver par jour
        $firebaseClient = FirebaseClientFacade::getCollection('archives/' . Carbon::now()->format('Y_m_d'));

        foreach ($dettes as $dette) {
            // Préparer les données pour l'archivage
            $data = [
                'dette' => $dette->toArray(),
                'details_dette' => $dette->details_dette->toArray(),
                'paiements' => $dette->paiements->toArray(),
                'client' => $dette->client->toArray(),
                'archived_at' => now(),
            ];

            // Insérer les données dans Firebase
            $firebaseClient->getReference()->push($data);

            // Supprimer les données locales une fois archivées
            $dette->details_dette()->delete();
            $dette->paiements()->delete();
            $dette->delete();
        }

        DB::commit(); 

        echo "Les dettes soldées ont été archivées avec succès dans Firebase.\n";
    }
}
