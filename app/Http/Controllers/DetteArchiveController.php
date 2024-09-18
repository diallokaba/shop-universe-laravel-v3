<?php

namespace App\Http\Controllers;

use App\Services\DetteServiceInterface;
use Illuminate\Http\Request;

use App\Facades\MongoClientFacade as MongoClient;
use App\Models\Dette;
use App\Models\Paiement;
use Exception;
use Illuminate\Support\Facades\DB;
use MongoDB\Driver\Exception\Exception as MongoDBException;

class DetteArchiveController extends Controller
{
    private $db;
    private $detteService;
    public function __construct(DetteServiceInterface $detteService){
        $mongoClient = MongoClient::getClient();
        $this->db = $mongoClient->selectDatabase('shop-universe-laravel-archive');
        $this->detteService = $detteService;
    }

    public function archiveDette(Request $request){
        try{
            $data = [];
            if($request->has('date')){
                $date = $request->only('date');
                //get the request value
                $collectionName = 'archives_' . $date['date'];

                $collection = $this->db->selectCollection($collectionName);

                // Récupérer toutes les dettes archivées
                $archiveDebts = $collection->find()->toArray();

                if (empty($archiveDebts)) {
                    return response()->json(['data' => []],  200);
                }

                 // Préparation des données pour la réponse
                
                foreach ($archiveDebts as $dette) {
                    $data[] = [
                        'collectionName' => $collectionName,
                        'dette' => $dette['dette'],
                    ];  
                }
            }else if($request->has('telephone')){
                $telephone = $request->get('telephone');
                //filter collection by client phone number
                $collections = $this->db->listCollections();
                foreach ($collections as $collectionInfo) {
                    $collectionName = $collectionInfo->getName();
                    $collection = $this->db->selectCollection($collectionName);
                    // Récupérer toutes les dettes archivées
                    $archiveDebts = $collection->find()->toArray();
                    //$archiveDebts = $collection->find(['dette.client.telephone' => '781234581'])->toArray();
                    foreach ($archiveDebts as $dette) {
                        if($dette['dette']['client']['telephone'] == $telephone){
                            $data[] = [
                                'collectionName' => $collectionName,
                                'dette' => $dette['dette'],
                            ];
                        }
                    }
                }
            }else{
                $collections = $this->db->listCollections();

                foreach ($collections as $collectionInfo) {
                    $collectionName = $collectionInfo->getName();
                    $collection = $this->db->selectCollection($collectionName);
                    // Récupérer toutes les dettes archivées
                    $archiveDebts = $collection->find()->toArray();

                    if (empty($archiveDebts)) {
                        return response()->json(['data' => []],  200);
                    }

                    foreach ($archiveDebts as $document) {
                        $data[] = [
                            'collectionName' => $collectionName,
                            'dette' => $document['dette'],
                        ];
                    }
                }
            }

           
            return response()->json(['data' => $data], 200);
            
        }catch(Exception $e){
            return response()->json(['error' => 'Erreur lors de la récupération des dettes archivées : ' . $e->getMessage()], 500);
        }
    }

    public function archiveClientDebts($id){
        try{
            $collections = $this->db->listCollections();
            $data = [];
            $id = (int)$id;
            foreach ($collections as $collectionInfo) {
                $collectionName = $collectionInfo->getName();
                $collection = $this->db->selectCollection($collectionName);
                // Récupérer toutes les dettes archivées
                $archiveDebts = $collection->find()->toArray();

                foreach ($archiveDebts as $document) {
                    if($document['dette']['client_id'] == $id){
                        $data[] = [
                            'collectionName' => $collectionName,
                            'dette' => $document['dette'],
                        ];
                    }
                }
            }

            return response()->json(['data' => $data], 200);
        }catch(Exception $e){
            return response()->json(['error' => 'Erreur lors de la récupération des dettes archivés de ce client ' . $e->getMessage()], 500);
        }
    }

    public function archiveById($id){
        try{
            $collections = $this->db->listCollections();
            $data = null;
            $id = (int)$id;
            foreach ($collections as $collectionInfo) {
                $collectionName = $collectionInfo->getName();
                $collection = $this->db->selectCollection($collectionName);
                // Récupérer toutes les dettes archivées
                $archiveDebts = $collection->find()->toArray();

                if (empty($archiveDebts)) {
                    return response()->json(['data' => []],  200);
                }

                foreach ($archiveDebts as $document) {
                    if($document['dette']['id'] == $id){
                        $data = [
                            'collectionName' => $collectionName,
                            'dette' => $document['dette'],
                        ];
                    }
                    break;
                }
            }

            return response()->json(['data' => $data], 200);
        }catch(Exception $e){
            return response()->json(['error' => 'Erreur lors de la récupération des dettes archivés de ce client ' . $e->getMessage()], 500);
        }
    }

    public function restaureDataFromOnlineByDate($date){
        
        try{
            DB::beginTransaction(); // Démarrer une transaction pour assurer l'intégrité des données
            
            $collectionName = 'archives_'. $date;
            $collection = $this->db->selectCollection($collectionName);

            // Récupérer toutes les dettes archivées
            $archiveDebts = $collection->find()->toArray();

            if (empty($archiveDebts)) {
                return response()->json(['message' => 'Aucune donnée à restaurer'],  200);
            }

            foreach($archiveDebts as $archive){
                $dette = Dette::create([
                    'montant' => $archive['dette']['montant'],
                    'client_id' => $archive['dette']['client_id'],
                    'statut' => $archive['dette']['statut'],
                    'created_at' => $archive['dette']['created_at'],
                    'updated_at' => now()
                ]);

                foreach($archive['dette']['paiements'] as $paiement){
                    Paiement::create([
                        'dette_id' => $dette->id,
                        'montant' => $paiement['montant'],
                        'created_at' => $paiement['created_at'],
                        'updated_at' => now(),
                    ]);
                }

                foreach ($archive['dette']['articles'] as $article) {
                    $articleId = $article["pivot"]['article_id'];
                    $qteVente = $article["pivot"]['qteVente'];
                    $prixVente = $article["pivot"]['prixVente'];
                    $created_at = $article["pivot"]['created_at'];

                    $dette->articles()->attach($articleId, [
                        'qteVente' => $qteVente, 
                        'prixVente' => $prixVente, 
                        'created_at' => $created_at, 
                        'updated_at' => now()
                    ]);
                }
            }

            // Vider la collection de dettes
            $collection->drop();

            DB::commit(); // Valider la transaction

            return response()->json(['message' => 'Données restaurées avec succès'], 200);
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la restauration des données : '. $e->getMessage()], 500);
        }catch(MongoDBException $e){
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la restauration des données : '. $e->getMessage()], 500);
        }
    }

    public function restaureDataFromOnlineById($id){
        try{
            DB::beginTransaction(); // Démarrer une transaction pour assurer l'intégrité des données
            
            $collections = $this->db->listCollections();
            $id = (int)$id;
            $isFind = false;

            foreach ($collections as $collectionInfo) {
                $collectionName = $collectionInfo->getName();
                $collection = $this->db->selectCollection($collectionName);
                // Récupérer toutes les dettes archivées
                $archiveDebts = $collection->find()->toArray();

                foreach($archiveDebts as $archive){
                    if($archive['dette']['id'] == $id){
                        $isFind = true;
                        $dette = Dette::create([
                            'montant' => $archive['dette']['montant'],
                            'client_id' => $archive['dette']['client_id'],
                            'statut' => $archive['dette']['statut'],
                            'created_at' => $archive['dette']['created_at'],
                            'updated_at' => now()
                        ]);
        
                        foreach($archive['dette']['paiements'] as $paiement){
                            Paiement::create([
                                'dette_id' => $dette->id,
                                'montant' => $paiement['montant'],
                                'created_at' => $paiement['created_at'],
                                'updated_at' => now(),
                            ]);
                        }

                        foreach ($archive['dette']['articles'] as $article) {
                            $articleId = $article["pivot"]['article_id'];
                            $qteVente = $article["pivot"]['qteVente'];
                            $prixVente = $article["pivot"]['prixVente'];
                            $created_at = $article["pivot"]['created_at'];
        
                            $dette->articles()->attach($articleId, [
                                'qteVente' => $qteVente, 
                                'prixVente' => $prixVente, 
                                'created_at' => $created_at, 
                                'updated_at' => now()
                            ]);
                        }
                        // Supprimer la dette spécifique de la collection
                        if($collection->countDocuments() == 1){
                            $collection->drop();
                        }else{
                            $collection->deleteOne(['dette.id' => $id]);
                        }
                        
                        break 2; // Quitter les deux boucles dès que la dette est trouvée et traitée
                    }
                }

            }

            if(!$isFind){
                return response()->json(['message' => 'Aucune dette trouvée avec l\'id ' . $id], 404);
            }

            DB::commit(); // Valider la transaction

            return response()->json(['message' => 'Données restaurées avec succès'], 200);
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la restauration des données : '. $e->getMessage()], 500);
        }catch(MongoDBException $e){
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la restauration des données : '. $e->getMessage()], 500);
        }
    }


    public function restaureClientDebtByClientId($id){
        try{
            DB::beginTransaction();
            $collections = $this->db->listCollections();
            $id = (int)$id;
            foreach ($collections as $collectionInfo) {
                $collectionName = $collectionInfo->getName();
                $collection = $this->db->selectCollection($collectionName);
                // Récupérer toutes les dettes archivées
                $archiveDebts = $collection->find()->toArray();

                foreach ($archiveDebts as $archive) {

                    if($archive['dette']['client_id'] == $id){

                        $dette = Dette::create([
                            'montant' => $archive['dette']['montant'],
                            'client_id' => $archive['dette']['client_id'],
                            'statut' => $archive['dette']['statut'],
                            'created_at' => $archive['dette']['created_at'],
                            'updated_at' => now()
                        ]);
        
                        foreach($archive['dette']['paiements'] as $paiement){
                            Paiement::create([
                                'dette_id' => $dette->id,
                                'montant' => $paiement['montant'],
                                'created_at' => $paiement['created_at'],
                                'updated_at' => now(),
                            ]);
                        }

                        foreach ($archive['dette']['articles'] as $article) {
                            $articleId = $article["pivot"]['article_id'];
                            $qteVente = $article["pivot"]['qteVente'];
                            $prixVente = $article["pivot"]['prixVente'];
                            $created_at = $article["pivot"]['created_at'];
        
                            $dette->articles()->attach($articleId, [
                                'qteVente' => $qteVente, 
                                'prixVente' => $prixVente, 
                                'created_at' => $created_at, 
                                'updated_at' => now()
                            ]);
                        }
                    
                        if($collection->countDocuments() == 1){
                            $collection->drop();
                        }else{
                            $collection->deleteOne(['dette.id' => $id]);
                        }

                    }
                }
            }

            DB::commit(); 

            return response()->json(['message' => 'Données restaurées avec succès'], 200);
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la restauration des données : '. $e->getMessage()], 500);
        }catch(MongoDBException $e){
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la restauration des données : '. $e->getMessage()], 500);
        }
    }
}
