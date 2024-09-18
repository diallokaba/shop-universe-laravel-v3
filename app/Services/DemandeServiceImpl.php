<?php

namespace App\Services;

use App\Jobs\SendNotificationToShopKeeperForDemande;
use App\Models\Client;
use App\Models\Demande;
use App\Models\Dette;
use App\Repositories\ArticleRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;

class DemandeServiceImpl implements DemandeServiceInterface{

    private $articleRepository;
    public function __construct(ArticleRepositoryInterface $articleRepository){
        $this->articleRepository = $articleRepository;
    }

    public function create(array $data){
        try{
            DB::beginTransaction();
            $user = auth()->user();
            $client = Client::where('user_id', $user->id)->first();

            if (!$client) {
                throw new Exception("Client non trouvé.");
            }
            
            if($client->category_client_id == 2 && $client->max_montant !=null){// Silver
                $totalDebts = $this->calculateTotalDebts($client);

                if($totalDebts >= $client->max_montant){
                    throw new Exception("Vous ne pouvez pas faire de demande dette car votre montant maximum de dette est atteint");
                }
            }

            if($client->category_client_id == 3){ // Bronze

                $dette = Dette::where('client_id', $client->id)
                ->where('statut', 'nonsolde')
                ->exists();

                if($dette){
                    throw new Exception("Vous ne pouvez pas faire de demande dette car vous avez deja une demande en cours");
                }
            }
            $montant = 0;
            $demande = Demande::create([
                'client_id' => $client->id,
                'montant' => $montant,
            ]);

            $articleSuccess = [];
            $articleFailed = [];
            foreach($data["details_article_demande"] as $detail){
                $articleId = $detail["articleId"];
                $qteDemande = $detail["qteDemande"];
    
                $article = $this->articleRepository->find($articleId);
                if($article){
                    $montant += $qteDemande * $article->prix;
    
                    $demande->articles()->attach($articleId, ['qteDemande' => $qteDemande, 'created_at' => now(), 'updated_at' => now()]);
    
                    $articleSuccess[] = $articleId;
                }else{
                    $articleFailed[] = $articleId;
                }                
            }

            if(empty($articleSuccess)){
                DB::rollBack();
                throw new Exception("Impossible de creer cette demande car aucun article n'a pu etre ajouter");
            }

            $demande->montant = $montant;
            $demande->save();

            //Dispatcher le job pour envoyer une notification asynchrone aux shopkeepers
            SendNotificationToShopKeeperForDemande::dispatch();

            DB::commit();

            return $demande->load('articles');
        }catch(Exception $e){
            DB::rollBack();
            throw new Exception("Erreur lors de la creation de la demande " . $e->getMessage());
        }
    }


    private function calculateTotalDebts($client){
        $dettes = Dette::with('paiements')
                    ->where('client_id', $client->id)
                    ->where('statut', 'nonsolde')
                    ->get();

        $totalDebts = $dettes->sum(function ($dette) {
            return $dette->montant - $dette->paiements->sum('montant');
        });

        return $totalDebts;
    }

    
    public function getDemandeWithFilterPossibility($request){
        $user = auth()->user();

        // Récupérer le client associé à cet utilisateur
        $client = Client::where('user_id', $user->id)->first();

        if (!$client) {
            throw new Exception('Client non trouvé.');
        }

        // Récupérer les demandes du client
        $query = Demande::where('client_id', $client->id);

        // Appliquer le filtre par statut s'il est fourni dans la requête
        if ($request->has('statut')) {
            $statut = $request->input('statut');
            $query->where('statut', $statut);
        }

        // Exécuter la requête et récupérer les résultats
        $demandes = $query->get();

        return $demandes;
    }
}