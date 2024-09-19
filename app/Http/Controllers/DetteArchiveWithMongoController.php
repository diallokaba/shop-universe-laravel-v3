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

class DetteArchiveWithMongoController extends Controller
{
    private $db;
    public function __construct(){
        $mongoClient = MongoClient::getClient();
        $this->db = $mongoClient->selectDatabase('shop-universe-laravel-archive');

    }


    /**
     * @OA\Get(
     *     path="/api/v1/dettes/archive",
     *     operationId="GetAllAllArchiveDebtWithOptionalFilters",
     *     tags={"AllArchiveDebtsOptionalFilters"},
     *     summary="Get all archive debts with optional filters",
     *     description="Récupération de toutes les dettes archivées avec possibilité de filtrer les dettes par telephone ou par date",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="telephone",
     *         in="query",
     *         description="Filterer les dettes archichées par telephone",
     *         required=false,
     *     ),
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Filterer les dettes archichées par date",
     *         required=false,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Opération reussie",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Resource Not Found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
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
                    return response()->json(['data' => [], 'message' => 'Aucune donnée trouvée à cette date'],  404);
                }

                
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

    /**
     * @OA\Get(
     *     path="/api/v1/archive/clients/{id}/dettes",
     *     operationId="GetAllArchiveClientDebtsByClientId",
     *     tags={"GetArchiveDebtsByClientId"},
     *     summary="Get Archive Debts By Client ID",
     *     description="Récupération de toutes les archivées du client par son identifiant",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Récupérer toutes les dettes archivées du client par son ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=17)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Opération reussie",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Resource Not Found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v1/archive/dettes/{id}",
     *     operationId="GetArchiveDebtsById",
     *     tags={"GetArchiveDebtById"},
     *     summary="Get Archive Debt By ID",
     *     description="Récupération d'une dette par son ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Récupérer la dette archivée par son ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=17)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Opération reussie",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Resource Not Found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
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

     /**
     * @OA\Get(
     *     path="/api/v1/restaure/{date}",
     *     operationId="RestaureArchiveDebtByGivenDate",
     *     tags={"RestaureArchiveDebtByGivenDate"},
     *     summary="Restaure Archive Debt By Given Date",
     *     description="Restauration d'une dette à travers une date passée en paramètre",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="date",
     *         in="path",
     *         description="Restaurer des dettes archivées à travers une date",
     *         required=true,
     *         @OA\Schema(type="string", example="2022_01_01")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Opération reussie",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Resource Not Found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function restaureDataFromOnlineByDate($date){
        
        try{
            DB::beginTransaction(); // Démarrer une transaction pour assurer l'intégrité des données
            
            $collectionName = 'archives_'. $date;
            $collection = $this->db->selectCollection($collectionName);

            // Récupérer toutes les dettes archivées
            $archiveDebts = $collection->find()->toArray();

            $nbRestaure = 0;

            if(!empty($archiveDebts)){
                foreach($archiveDebts as $archive){
                    $nbRestaure++;
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
            }
            
            // Vider la collection de dettes
            $collection->drop();

            DB::commit(); // Valider la transaction

            if($nbRestaure > 0){
                return response()->json(['message' => 'Données restaurées avec succès'], 200);
            }else{
                return response()->json(['message' => 'Aucune donnée n\'a été restaurer'], 200);
            }
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la restauration des données : '. $e->getMessage()], 500);
        }catch(MongoDBException $e){
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de la restauration des données : '. $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/restaure/dette/{id}",
     *     operationId="RestaureArchiveDebtByGivenId",
     *     tags={"RestaureArchiveDebtByGivenId"},
     *     summary="Restaure Archive Debt By Given ID",
     *     description="Restauration d'une dette à travers son ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Restaurer une dette archivée à travers son ID",
     *         required=true,
     *         @OA\Schema(type="integer", example="4")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Opération reussie",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Resource Not Found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
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

                if(!empty($archiveDebts)){
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

    /**
     * @OA\Get(
     *     path="/api/v1/restaure/dette/client/{id}",
     *     operationId="RestaureAllClientArchiveDebtsByClientID",
     *     tags={"RestaureAllClientArchiveDebtsByClientID"},
     *     summary="Restaure all client Archive Debt By client ID",
     *     description="Restaurer toutes les dettes d'un client à travers son ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Restaurer toutes les dettes archivées à travers son ID",
     *         required=true,
     *         @OA\Schema(type="integer", example="4")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Opération reussie",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Resource Not Found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function restaureClientDebtByClientId($id){
        try{
            DB::beginTransaction();
            $collections = $this->db->listCollections();
            $id = (int)$id;
            $nbArchive = 0;
            foreach ($collections as $collectionInfo) {
                $collectionName = $collectionInfo->getName();
                $collection = $this->db->selectCollection($collectionName);
                // Récupérer toutes les dettes archivées
                $archiveDebts = $collection->find()->toArray();

                if(!empty($archiveDebts)){
                    foreach ($archiveDebts as $archive) {

                        if($archive['dette']['client_id'] == $id){

                            $nbArchive++;
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
                
            }

            DB::commit(); 

            if($nbArchive == 0){
                return response()->json(['message' => 'Aucune dette n\'a été resaturée pour ce client'], 404);
            }
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
