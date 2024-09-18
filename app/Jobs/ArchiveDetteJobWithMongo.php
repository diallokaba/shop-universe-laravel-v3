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

        echo 'Archive debts Job started at ' . now() . ' with Mongodb';
        try {
            
            $dettes = Dette::with(['paiements', 'client', 'articles'])->where('statut', 'solde')->get();

            if(!empty($dettes)){
                DB::beginTransaction();
                
                $mongoClient = MongoClient::getClient();
                $db = $mongoClient->selectDatabase('shop-universe-laravel-archive'); // Sélectionner la base de données
                $collection = $db->selectCollection('archives_' . Carbon::now()->format('Y_m_d')); // Sélectionner la collection du jour

                foreach ($dettes as $dette) {
                    $data = [
                        'dette' => $dette->toArray()
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

            }else{
                echo 'Aucune dette à archiver';
            }            

        } catch (\Exception $e) {
            // Annuler la transaction en cas d'erreur
            DB::rollBack();
            Log::error('Erreur lors de l\'archivage des dettes : ' . $e->getMessage());
            echo "Erreur lors de l'archivage des dettes : " . $e->getMessage() . "\n";
        }
    }
}
