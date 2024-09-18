<?php

namespace App\Http\Controllers;

use App\Enums\StatusResponseEnum;
use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\UserServiceInterface;
use App\Traits\RestResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    use RestResponseTrait;
    private $userService;

    public function __construct(UserServiceInterface $userService){
        $this->userService = $userService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users",
     *     operationId="StoreUser",
     *     tags={"StoreUser"},
     *     summary="Store User",
     *     description="Creation d'un compte utilisateur avec le role ADMIN ou BOUTIQUIER",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"nom", "prenom", "photo", "login", "password", "password_confirmation", "role"},
     *                 @OA\Property(property="nom", type="string", example="John"),
     *                 @OA\Property(property="prenom", type="string", example="Doe"),
     *                 @OA\Property(property="photo", type="string", example="https://images.pexels.com/photos/633432/pexels-photo-633432.jpeg?auto=compress&cs=tinysrgb&w=600"),
     *                 @OA\Property(property="login", type="string", example="johndoe"),
     *                 @OA\Property(property="password", type="string", format="password", example="Passer@123"),
     *                 @OA\Property(property="password_confirmation", type="string", format="password", example="Passer@123"),
     *                 @OA\Property(property="role", type="object",
     *                     @OA\Property(property="id", type="integer", example="1")
     *                 )
     *             )
     *         ),
     *         @OA\MediaType(
     *            mediaType="application/json",
     *            @OA\Schema(
     *               type="object",
     *               required={"nom", "prenom", "photo", "login", "password", "password_confirmation", "role"},
     *               @OA\Property(property="nom", type="string", example="John"),
     *               @OA\Property(property="prenom", type="string", example="Doe"),
     *               @OA\Property(property="photo", type="string", example="https://images.pexels.com/photos/633432/pexels-photo-633432.jpeg?auto=compress&cs=tinysrgb&w=600"),
     *               @OA\Property(property="login", type="string", example="johndoe"),
     *               @OA\Property(property="password", type="string", format="password", example="Passer@123"),
     *               @OA\Property(property="password_confirmation", type="string", format="password", example="Passer@123"),
     *               @OA\Property(property="role", type="object",
     *                   @OA\Property(property="id", type="integer", example="1")
     *               )
     *            )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte utilisateur créé avec succès",
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
    public function store(UserRequest $request) {
        try{
            $userRequest = $request->only('nom', 'prenom', 'login', 'password', 'photo', 'role');
            $user = $this->userService->create($userRequest);
            return $this->sendResponse($user, StatusResponseEnum::SUCCESS, 'Utilisateur créé avec succès', 201);
        }catch(Exception $e){
            return $this->sendResponse(null, StatusResponseEnum::ECHEC, $e->getMessage(), 500);
        }
    }

    //tous les utilisateurs avec possibilité de filtrer par role et/ou par active
    /**
     * @OA\Get(
     *     path="/api/v1/users",
     *     operationId="GetAllUsersWithOptionalFilters",
     *     tags={"AllUsersWithOptionalFilters"},
     *     summary="Get all users with optional filters",
     *     description="Récupération des utilisateurs avec possibilité de filtrer par role et/ou par active",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="Filterer les utilisateurs par role (admin ou boutiquier)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"admin", "boutiquier"})
     *     ),
     *     @OA\Parameter(
     *         name="active",
     *         in="query",
     *         description="Filterer les utilisateurs par status (oui ou non)",
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
    public function index(Request $request) {
        try{
            $users = $this->userService->query($request);
            
            if ($users->isNotEmpty()) {
                return $this->sendResponse($users, StatusResponseEnum::SUCCESS, 'Utilisateurs récupérés avec succès.');
            } else {
                return $this->sendResponse([], StatusResponseEnum::SUCCESS, 'Aucun utilisateur trouvé.');
            }
        }catch(Exception $e){
            return $this->sendResponse(null, StatusResponseEnum::ECHEC, $e->getMessage(), 500);
        }
        
        //return $this->sendResponse(UserResource::collection($users), StatusResponseEnum::SUCCESS);
    }
}
