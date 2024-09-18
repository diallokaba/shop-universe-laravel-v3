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
use Illuminate\Support\Facades\Log;

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
        echo 'Archive debts Job started at ' . now() . ' with Firebase';
        try{

            $dettes = Dette::with(['paiements', 'client', 'articles'])->where('statut', 'solde')->get();

            if($dettes->isEmpty()){
                echo 'Aucune dette à archiver';
                return;
            }

            DB::beginTransaction();
            
            $firebaseClient = FirebaseClientFacade::getCollection('archives/' . Carbon::now()->format('Y_m_d'));
            $archiveName =  'archives/' . Carbon::now()->format('Y_m_d');

            foreach ($dettes as $dette) {
                $data = [
                    'dette' => $dette->toArray()
                ];
    
                // Insérer les données dans Firebase
                FirebaseClientFacade::upsertDocument($archiveName, uniqid().'_'.time(), $data);
    
                // Supprimer les données locales une fois archivées
                DB::table('details_dette')->where('dette_id', $dette->id)->delete();
                $dette->paiements()->delete();
                $dette->delete();
            }
    
            DB::commit(); 
    
            echo "Les dettes soldées ont été archivées avec succès dans Firebase.\n";
            
        }catch (\Exception $e) {
            // Annuler la transaction en cas d'erreur
            DB::rollBack();
            Log::error('Erreur lors de l\'archivage des dettes : ' . $e->getMessage());
            echo "Erreur lors de l'archivage des dettes : " . $e->getMessage() . "\n";
        }          
    }
}
