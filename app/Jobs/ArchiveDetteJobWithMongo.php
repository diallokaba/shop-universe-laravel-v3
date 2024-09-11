<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Facades\MongoClientFacade as MongoClient;
use App\Models\Dette;
use Carbon\Carbon;

class ArchiveDetteJobWithMongo implements ShouldQueue
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
        $mongoClient = MongoClient::getClient();
        $db = $mongoClient->selectDatabase(env('MONGO_DATABASE')); // Sélectionner la base de données
        $collection = $db->selectCollection('archives_' . Carbon::now()->format('Y_m_d')); // Sélectionner la collection du jour
        
        $dettes = Dette::with(['details_dette', 'paiements', 'client'])->where('statut', 'solde')->get();

        foreach ($dettes as $dette) {
            $data = [
                'dette' => $dette->toArray(),
                'details_dette' => $dette->details_dette->toArray(),
                'paiements' => $dette->paiements->toArray(),
                'client' => $dette->client->toArray(),
                'archived_at' => now(),
            ];

            $collection->insertOne($data);

            $dette->details_dette()->delete();
            $dette->paiements()->delete();
            $dette->delete();
        }

        echo "Les dettes soldées ont été archivées avec succès dans MongoDB.\n";
    }
}
