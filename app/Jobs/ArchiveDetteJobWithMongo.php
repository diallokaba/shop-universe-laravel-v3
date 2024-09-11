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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        // Démarrage de la transaction SQL
        DB::beginTransaction();

        try {
            $mongoClient = MongoClient::getClient();
            $db = $mongoClient->selectDatabase(env('MONGO_DATABASE')); // Sélectionner la base de données
            $collection = $db->selectCollection('archives_' . Carbon::now()->format('Y_m_d')); // Sélectionner la collection du jour
            
            $dettes = Dette::with(['paiements', 'client'])->where('statut', 'solde')->get();

            foreach ($dettes as $dette) {
                $data = [
                    'dette' => $dette->toArray(),
                    'details_dette' => $dette->getDetailsDette()->toArray(),
                    'paiements' => $dette->paiements->toArray(),
                    'client' => $dette->client->toArray(),
                    'archived_at' => now(),
                ];

                // Insertion dans MongoDB
                $insertResult = $collection->insertOne($data);

                // Vérification du succès de l'insertion
                if ($insertResult->getInsertedCount() === 1) {
                    // Supprimer les détails de la dette dans SQL
                    DB::table('details_dette')->where('dette_id', $dette->id)->delete();
                    $dette->paiements()->delete();
                    $dette->delete();
                } else {
                    // Si l'insertion échoue, lancer une exception
                    throw new \Exception('Échec de l\'insertion dans MongoDB');
                }
            }

            // Validation de la transaction SQL
            DB::commit();

            echo "Les dettes soldées ont été archivées avec succès dans MongoDB.\n";

        } catch (\Exception $e) {
            // Annuler la transaction en cas d'erreur
            DB::rollBack();
            Log::error('Erreur lors de l\'archivage des dettes : ' . $e->getMessage());
            echo "Erreur lors de l'archivage des dettes : " . $e->getMessage() . "\n";
        }
    }
}
