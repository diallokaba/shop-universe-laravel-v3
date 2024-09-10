<?php

namespace App\Services;

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

            $dette = $this->detteRepository->create(["client_id" => $clientId, "montant" => $montantDette]);
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

                    $dette->articles()->attach($articleId, ['qteVente' => $qteVente, 'prixVente' => $prixVente]);

                    $articleSuccess[] = $articleId;
                  
                }else{
                    $articleFailed[] = $articleId;
                }                
            }

            $dette->montant = $montantDette;
            $dette->save();

            $paiement = null;
            
            if (isset($data['paiement']) && !empty($data['paiement'])) {
                $montant = $data['paiement']['montant'];
                if ($montant > 0 && $montant <= $montantDette) {
                    $paiement = Paiement::create([
                        'dette_id' => $dette->id,
                        'montant' => $montant
                    ]);
                }
            }

            DB::commit();
            
            return ["dette" => $dette, "success" => $articleSuccess, "failed" => $articleFailed, "paiement" => $paiement];
        }catch(Exception $e){
            DB::rollBack();
            throw new Exception('Erreur lors de la creation de la dette : ' . $e->getMessage());
        }
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
        $dette = $this->find($id);
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

        $paiement = Paiement::create([
            'dette_id' => $dette->id,
            'montant' => $montant
        ]);

        return $dette->with('paiements')->find($dette->id);
    }

    public function find($id){
        return $this->detteRepository->find($id);
    }
}