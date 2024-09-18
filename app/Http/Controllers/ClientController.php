<?php

namespace App\Http\Controllers;

use App\Enums\StatusResponseEnum;
use App\Http\Requests\ClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use App\Traits\RestResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Facades\ClientServiceFacade as clientService;
use App\Http\Middleware\ShowResponse;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    use RestResponseTrait;

   /**
     * @OA\Post(
     *     path="/api/v1/clients",
     *     operationId="StoreClient",
     *     tags={"StoreClient"},
     *     summary="Store Client",
     *     description="Création d'un client avec la possibilité de lui créer un compte",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"surname", "telephone"},
     *                 @OA\Property(property="surname", type="string", example="bobou"),
     *                 @OA\Property(property="telephone", type="string", example="761434522"),
     *                 @OA\Property(property="adresse", type="string", example="namek"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="nom", type="string", example="Mahmoud"),
     *                     @OA\Property(property="prenom", type="string", example="Mahmoud"),
     *                     @OA\Property(property="login", type="string", example="Mahmoudine"),
     *                     @OA\Property(property="photo", type="string", example="https://images.pexels.com/photos/633432/pexels-photo-633432.jpeg?auto=compress&cs=tinysrgb&w=600"),
     *                     @OA\Property(property="password", type="string", format="password", example="Passer@123"),
     *                     @OA\Property(property="password_confirmation", type="string", format="password", example="Passer@123"),
     *                     @OA\Property(property="role", type="object",
     *                         @OA\Property(property="id", type="integer", example="3")
     *                     )
     *                 )
     *             )
     *         ),
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"surname", "telephone"},
     *                 @OA\Property(property="surname", type="string", example="bobou"),
     *                 @OA\Property(property="telephone", type="string", example="761434522"),
     *                 @OA\Property(property="adresse", type="string", example="namek"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="nom", type="string", example="Mahmoud"),
     *                     @OA\Property(property="prenom", type="string", example="Mahmoud"),
     *                     @OA\Property(property="login", type="string", example="Mahmoudine"),
     *                     @OA\Property(property="photo", type="string", example="https://images.pexels.com/photos/633432/pexels-photo-633432.jpeg?auto=compress&cs=tinysrgb&w=600"),
     *                     @OA\Property(property="password", type="string", format="password", example="Passer@123"),
     *                     @OA\Property(property="password_confirmation", type="string", format="password", example="Passer@123"),
     *                     @OA\Property(property="role", type="object",
     *                         @OA\Property(property="id", type="integer", example="3")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Client créé avec succès",
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
    /*public function store2(ClientRequest $request){
        try {
            DB::beginTransaction();
            $clientRequest = $request->only('surname','adresse','telephone');
            $client= Client::create($clientRequest);
            if ($request->has('user')){
                $roleId = $request->input('user.role.id');
                $role = Role::find($roleId);
                $user = User::create([
                    'nom' => $request->input('user.nom'),
                    'prenom' => $request->input('user.prenom'),
                    'login' => $request->input('user.login'),
                    'password' => $request->input('user.password'),
                    'photo' => $request->input('user.photo'),
                    'role_id' => $role->id
                ]);
                $client->user()->associate($user);
                $client->save();
                //$user->client()->save($client);
            }
            DB::commit();
            return $this->sendResponse(new ClientResource($client), StatusResponseEnum::SUCCESS, 'Client créé avec succès', 201);
        }catch (Exception $e){
            DB::rollBack();
            return $this->sendResponse(['error' => $e->getMessage()], StatusResponseEnum::ECHEC, 500);
        }
    }*/

    /*public function store(ClientRequest $request){
        try {
            $clientRequest = $request->only('surname','adresse','telephone', 'user');
            $client = clientService::create($clientRequest);
            return $this->sendResponse(new ClientResource($client), StatusResponseEnum::SUCCESS, 'Client créé avec succès', 201);
        }catch (Exception $e){
            // Log de l'erreur pour plus de détails
            Log::error('Erreur lors de la création du client: ' . $e->getMessage(), ['exception' => $e]);
            return $this->sendResponse(['error' => $e->getMessage()], StatusResponseEnum::ECHEC, 500);
        }
    }*/

    public function store(ClientRequest $request){
      
        $clientRequest = $request->only('surname','adresse','telephone', 'user', 'category_client');
        $client = clientService::create($clientRequest);
        return $client;
        //return $this->sendResponse(new ClientResource($client), StatusResponseEnum::SUCCESS, 'Client créé avec succès', 201);
    }

    public function all(){
        return clientService::all();
    }

   /**
     * @OA\Get(
     *     path="/api/v1/clients",
     *     operationId="GetAllClientsWithOptionalFilters",
     *     tags={"AllClientsWithOptionalFilters"},
     *     summary="Get all Clients with optional filters",
     *     description="Récupération de tous les clients avec possibilité de filtrer les clients qui ont des comptes, qui n'ont pas de compte. Ceux dont leurs compte est actif ou inactif",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="comptes",
     *         in="query",
     *         description="Filterer les clients par comptes (oui ou non)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"oui", "non"})
     *     ),
     *     @OA\Parameter(
     *         name="active",
     *         in="query",
     *         description="Filterer les clients par etat (oui ou non)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"oui", "non"})
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
    public function index2(Request $request) {
        try{
            $clients = Client::query();
             // Filtrer par comptes (avec ou sans utilisateur)
            if ($request->has('comptes')) {
                $value = strtolower($request->get('comptes'));
                
                if ($value === 'oui') {
                    // Clients avec un compte utilisateur (user_id non null)
                    $clients->whereHas('user');
                } elseif ($value === 'non') {
                    // Clients sans compte utilisateur (user_id null)
                    $clients->doesntHave('user');
                }
            }

             // Filtrer par activité du compte utilisateur
            if ($request->has('active')) {
                $value = strtolower($request->get('active'));
                
                if ($value === 'oui') {
                    // Clients avec un compte utilisateur actif (active = 'OUI')
                    $clients->whereHas('user', function($query) {
                        $query->where('active', 'OUI');
                    });
                } elseif ($value === 'non') {
                    // Clients avec un compte utilisateur inactif (active != 'OUI')
                    $clients->whereHas('user', function($query) {
                        $query->where('active', 'NON');
                    });
                }
            }

            $clients = $clients->get();

            // Retourner la réponse
            if ($clients->isNotEmpty()) {
                return $this->sendResponse($clients, StatusResponseEnum::SUCCESS, 'Liste des clients.');
            } else {
                return $this->sendResponse([], StatusResponseEnum::SUCCESS, 'Pas de clients.');
            }
        }catch(Exception $e){
            return $this->sendResponse(null, StatusResponseEnum::ECHEC, $e->getMessage(), 500);
        }
        
        //return $this->sendResponse(UserResource::collection($users), StatusResponseEnum::SUCCESS);
    }

    public function index(Request $request) {
        try{
            $clients = Client::filter($request)->with('user')->get();
            
            // Retourner la réponse
            if ($clients->isNotEmpty()) {
                return $this->sendResponse(ClientResource::collection($clients), StatusResponseEnum::SUCCESS, 'Liste des clients.');
            } else {
                return $this->sendResponse([], StatusResponseEnum::SUCCESS, 'Pas de clients.');
            }
        }catch(Exception $e){
            return $this->sendResponse(null, StatusResponseEnum::ECHEC, $e->getMessage(), 500);
        }
        
        //return $this->sendResponse(UserResource::collection($users), StatusResponseEnum::SUCCESS);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/clients/telephone",
     *     operationId="SearchClientByTelephone",
     *     tags={"SearchClientByTelephone"},
     *     summary="Search Client By Telephone",
     *     description="Récupérer le client par son telephone",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"telephone"},
     *                 @OA\Property(property="telephone", type="string", example="785222794"),
     *             )
     *         ),
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"telephone"},
     *                 @OA\Property(property="telephone", type="string", example="761434522"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Opération réussie",
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
    public function getByTelephone(Request $request) {
        $telephone = $request->input('telephone');
        if (!$telephone) {
            return $this->sendResponse(null, StatusResponseEnum::ECHEC, 'Veuillez renseigner un numéro de téléphone.', 400);
        }
        $client = Client::where('telephone', $telephone)->first();
        if (!$client) {
            return $this->sendResponse(null, StatusResponseEnum::SUCCESS, 'Aucun client trouvé avec ce numéro de téléphone.', 404);
        }
        return $this->sendResponse($client, StatusResponseEnum::SUCCESS, 'Client trouvé.', 200);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/clients/{id}",
     *     operationId="GetClientById",
     *     tags={"GetClientById"},
     *     summary="Get Client By ID",
     *     description="Récupération du client par son ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Récupérer le client par son ID",
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
    public function getById($id){

        
        $client = clientService::find($id);
        
        // Vérifier si le client existe
        if (!$client) {
            return $this->sendResponse(null, StatusResponseEnum::ECHEC, 'Client non trouvé.', 404);
        }

        // Retourner le client trouvé
        return $this->sendResponse($client, StatusResponseEnum::SUCCESS, 'Client trouvé avec succès.', 200);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/clients/{id}/user",
     *     operationId="GetClientByIdWithUserAccount",
     *     tags={"GetClientByIdWithUserAccount"},
     *     summary="Get Client By ID with User Account",
     *     description="Récupération du client par son ID et son compte utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Récupérer le client par son ID et son compte utilisateur",
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
    public function clientWithHisAccount($id){        

        $client = clientService::clientWithHisAccount($id);
        
        // Vérifier si le client existe
        if (!$client) {
            return $this->sendResponse(null, StatusResponseEnum::ECHEC, 'Client non trouvé.', 404);
        }

        // Retourner le client trouvé
        return $this->sendResponse(new ClientResource($client), StatusResponseEnum::SUCCESS, 'Client trouvé avec succès.', 200);
    }

    public function debtsByClientId($id){
        $clientWithDebts = clientService::getClientWithHisDebts($id);
        
        if($clientWithDebts){
            return $this->sendResponse($clientWithDebts, StatusResponseEnum::SUCCESS, 'Client avec des dettes.', 200);
        }else{
            return $this->sendResponse(null, StatusResponseEnum::ECHEC, 'Client sans dettes.', 404);
        }
    }

}
