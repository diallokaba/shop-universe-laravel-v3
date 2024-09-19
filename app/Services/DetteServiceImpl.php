<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Dette;
use App\Models\Paiement;
use App\Repositories\ArticleRepositoryInterface;
use App\Repositories\DetteRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;

class DetteServiceImpl implements DetteServiceInterface{

    private $detteRepository;
    private $articleRepository;
    public function __construct(DetteRepositoryInterface $detteRepository, ArticleRepositoryInterface $articleRepository){
        $this->detteRepository = $detteRepository;
        $this->articleRepository = $articleRepository;
    }

    public function all(){
        return $this->detteRepository->all();
    }

    public function filter($request){
        try{
            $dettes = $this->detteRepository->filter();
            if($request && $request->input('statut')){
                $value =  strtolower($request->input('statut'));
                $dettes->with('client');
                if($value === 'solde'){
                    $dettes->where('statut', 'solde');
                    
                }else if($value === 'nonsolde'){
                    $dettes->where('statut', 'nonsolde');
                }
            }
            $dettes = $dettes->get();
            return $dettes;
        }catch(Exception $e){
            throw new Exception('Erreur lors de l\'affichage des dettes : ' . $e->getMessage());
        }
    }

    public function create(array $data)
    {
        try{
            DB::beginTransaction();
            $articleSuccess = [];
            $articleFailed = [];
            $montantDette = 0;
            $clientId = $data["client"]["id"];

            $client = Client::find($clientId);
            if($client->category_client_id == 3){ // Bronze

                $dette = Dette::where('client_id', $clientId)
                ->where('statut', 'nonsolde')
                ->exists();

                if($dette){
                    throw new Exception("Votre profil ne vous permet pas d'avoir deux dettes non soldées.");
                }
            }

            if($client->category_client_id == 2 && $client->max_montant !=null){// Silver
                $totalDebts = $this->calculateTotalDebts($client);

                if($totalDebts >= $client->max_montant){
                    throw new Exception("Vous ne pouvez pas faire de demande dette car votre montant maximum de dette est atteint");
                }
            }

            $dette = $this->detteRepository->create(["client_id" => $clientId, "montant" => $montantDette, "echeance" => $data["echeance"] ?? null]);
            if (!$dette) {
                throw new Exception('Erreur lors de la création de la dette. La dette n\'a pas pu être créée.');
            }

            foreach($data["details_dette"] as $detail){
                $articleId = $detail["articleId"];
                $qteVente = $detail["qteVente"];
                $prixVente = $detail["prixVente"];

                $article = $this->articleRepository->find($articleId);
                if($article && $article->quantite >= $qteVente){
                    $article->quantite -= $qteVente;
                    $article->save();

                    $montantDette += $qteVente * $prixVente;

                    $dette->articles()->attach($articleId, ['qteVente' => $qteVente, 'prixVente' => $prixVente, 'created_at' => now(), 'updated_at' => now()]);

                    $articleSuccess[] = $articleId;
                  
                }else{
                    $articleFailed[] = $articleId;
                }                
            }

            if(empty($articleSuccess)){
                DB::rollBack();
                throw new Exception("Impossible de creer la dette car aucun article n'a pu etre ajouter");
            }

            if($client->category_client_id == 2 && $client->max_montant !=null){// Silver
                $totalDebts = $this->calculateTotalDebts($client);
                $totalDebts += $montantDette;
                if($totalDebts >= $client->max_montant){
                    throw new Exception("Vous ne pouvez pas faire de demande dette car votre montant maximum de dette est atteint");
                }
            }

            $dette->montant = $montantDette;
            $dette->save();

            $paiement = null;
            
            if (isset($data['paiement']) && $montantDette > 0) {
                $montant = $data['paiement']['montant'];
                if($montantDette <= $montant){
                    DB::rollBack();
                    throw new Exception("Le montant du premier paiement doit etre inferieur au montant total de la la dette");
                }
                
                if ($montant > 0 && $montant < $montantDette) {
                    $paiement = Paiement::create([
                        'dette_id' => $dette->id,
                        'montant' => $montant
                    ]);
                }
            }

            if($client->category_client_id == 2 && $client->max_montant !=null){// Silver
                $totalDebts = $this->calculateTotalDebts($client);
                if($paiement){
                    $montantDette -= $paiement->montant;
                }
                $totalDebts += $montantDette;
                if($totalDebts >= $client->max_montant){
                    throw new Exception("Vous ne pouvez pas faire de demande dette car votre montant maximum de dette est atteint");
                }
            }

            DB::commit();
            
            return ["dette" => $dette, "success" => $articleSuccess, "failed" => $articleFailed, "paiement" => $paiement];
        }catch(Exception $e){
            DB::rollBack();
            throw new Exception('Erreur lors de la creation de la dette : ' . $e->getMessage());
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

    public function update(array $data, $id)
    {
        // TODO: Implement update() method.
    }

    public function delete($id)
    {
        // TODO: Implement delete() method.
    }

    public function paiement($id, $request){
        $dette = $this->detteRepository->find($id);
        if(!$dette){
           throw new Exception('La dette avec l\'id ' . $id . ' n\'existe pas.'); 
        }

        $paiements = Paiement::where('dette_id', $dette->id)->get();

        $totalPaiement = 0;

        if(!empty($paiements)){
            foreach ($paiements as $paiement) {
                $totalPaiement += $paiement['montant'];
            }
        }

        

        $montant = $request->input('montant');

        $totalPaiement += $montant;

        if($totalPaiement > $dette->montant){
            throw new Exception('Le montant total de paiement est superieur au montant de la dette.');
        }

        if($totalPaiement == $dette->montant){
            $dette->statut = 'solde';
            $dette->save();
        }

        $paiement = Paiement::create([
            'dette_id' => $dette->id,
            'montant' => $montant
        ]);

        return $dette->with('paiements')->find($dette->id);
    }

    public function find($id){
        //dd(gettype($id));
        if(!is_numeric($id)){
            throw new Exception('L\'id doit être un nombre entier');
        }
        $id = (int)$id;
        return $this->detteRepository->find($id)->with('client')->first();
    }

    public function detbsWithDetails($id){
        //dd(gettype($id));
        if(!is_numeric($id)){
            throw new Exception('L\'id doit être un nombre entier');
        }
        $id = (int)$id;
        return $this->detteRepository->detbsWithDetails($id);
    }

    public function debtsWithPayments($id){
        //dd(gettype($id));
        if(!is_numeric($id)){
            throw new Exception('L\'id doit être un nombre entier');
        }
        //$id = (int)$id;
        $dettes = $this->detteRepository->debtsWithPayments($id);
        return $dettes;
    }
}