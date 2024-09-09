<?php

namespace App\Http\Controllers;

use App\Enums\StatusResponseEnum;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\RegistreRequest;
use App\Http\Resources\ClientResource;
use App\Http\Resources\UserResource;
use App\Models\Client;
use App\Models\User;
use App\Services\AuthenticationServiceInterface;
use App\Services\CustomTokenGenerator;
use App\Traits\RestResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Info(
 *    title="Manager API for ShopManagement",
 *    version="1.0.0",
 * )
 */
class AuthController extends Controller
{

    use RestResponseTrait; 

    private $authService;

    public function __construct(AuthenticationServiceInterface $authService){
        $this->authService = $authService;
    }

    //Cet end point va permettre de créer un compte utilisateur pour un client
    //le client doit exister et les informations de l'utilisateur doivent etre valides
    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     operationId="Register",
     *     tags={"Register"},
     *     summary="User Register",
     *     description="Création de compte pour un client",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"nom", "prenom", "photo", "login", "password", "password_confirmation", "client"},
     *                 @OA\Property(property="nom", type="string", example="John"),
     *                 @OA\Property(property="prenom", type="string", example="Doe"),
     *                 @OA\Property(property="photo", type="string", example="https://images.pexels.com/photos/633432/pexels-photo-633432.jpeg?auto=compress&cs=tinysrgb&w=600"),
     *                 @OA\Property(property="login", type="string", example="johndoe"),
     *                 @OA\Property(property="password", type="string", format="password", example="Passer@123"),
     *                 @OA\Property(property="password_confirmation", type="string", format="password", example="Passer@123"),
     *                 @OA\Property(property="client", type="object",
     *                     @OA\Property(property="id", type="integer", example="1")
     *                 )
     *             )
     *         ),
     *         @OA\MediaType(
     *            mediaType="application/json",
     *            @OA\Schema(
     *               type="object",
     *               required={"nom", "prenom", "photo", "login", "password", "password_confirmation", "client"},
     *               @OA\Property(property="nom", type="string", example="John"),
     *               @OA\Property(property="prenom", type="string", example="Doe"),
     *               @OA\Property(property="photo", type="string", example="https://images.pexels.com/photos/633432/pexels-photo-633432.jpeg?auto=compress&cs=tinysrgb&w=600"),
     *               @OA\Property(property="login", type="string", example="johndoe"),
     *               @OA\Property(property="password", type="string", format="password", example="Passer@123"),
     *               @OA\Property(property="password_confirmation", type="string", format="password", example="Passer@123"),
     *               @OA\Property(property="client", type="object",
     *                   @OA\Property(property="id", type="integer", example="1")
     *               )
     *            )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créer avec succès",
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
    public function register(RegisterRequest $request){
        try {
            DB::beginTransaction();
            $userRequest = $request->only('nom', 'prenom', 'photo', 'login', 'password', 'client');

            $clientId = $request->input('client.id'); 
            if (!$clientId) {
                return $this->sendResponse(null, StatusResponseEnum::ECHEC, "L'ID client n'est pas fourni ou invalide.", 400);
            }

            $client = Client::find($clientId);

            if (!$client) {
                throw new Exception("Client non trouvé");
            }

            // Vérification si le client a déjà un utilisateur associé
            if ($client->user) { 
                return $this->sendResponse(null, StatusResponseEnum::ECHEC, 'Ce client a déjà un compte', 400);
            }

            $userRequest['role_id'] = 2;

            // Hachage du mot de passe
            $userRequest['password'] = Hash::make($userRequest['password']);

             // Création de l'utilisateur
            $user = User::create($userRequest);
          
            $client->user()->associate($user);
            $client->save();
            
           
            DB::commit();
            return $this->sendResponse(new ClientResource($client), StatusResponseEnum::SUCCESS, 'Compte créer avec succès', 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendResponse(['error' => $e->getMessage()], StatusResponseEnum::ECHEC, 500);
        }
    }

    /**
    * @OA\Post(
    *     path="/api/v1/login",
    *     operationId="Login",
    *     tags={"Login"},
    *     summary="User Login",
    *     description="Connexion de l'utilisateur...",
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *            mediaType="multipart/form-data",
    *            @OA\Schema(
    *               type="object",
    *               required={"login", "password"},
    *               @OA\Property(property="login", type="string", example="khalilThree"),
    *               @OA\Property(property="password", type="string", example="Passer@123"),
    *            ),
    *        ),
    *        @OA\MediaType(
    *            mediaType="application/json",
    *            @OA\Schema(
    *               type="object",
    *               required={"login", "password"},
    *               @OA\Property(property="login", type="string", example="khalilThree"),
    *               @OA\Property(property="password", type="string", example="Passer@123"),
    *            ),
    *        ),
    *    ),
    *    @OA\Response(
    *        response=200,
    *        description="Connexion réussie",
    *        @OA\JsonContent()
    *    ),
    *    @OA\Response(
    *        response=422,
    *        description="Unprocessable Entity",
    *        @OA\JsonContent()
    *    ),
    *    @OA\Response(response=400, description="Bad request"),
    *    @OA\Response(response=404, description="Resource Not Found"),
    *    @OA\Response(response=500, description="Internal server error"),
    * )
    */
    public function login(LoginRequest $request){
        $credentials = $request->only('login', 'password');

        $user = User::where('login', $credentials['login'])->first();
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return $this->sendResponse(null, StatusResponseEnum::ECHEC, 'Login ou mot de passe incorrect', 401);
        }

        $token = $this->authService->authenticate($credentials);

        if($token){
            return response()->json(['token' => $token], 200);
        }
        return $this->sendResponse(null, StatusResponseEnum::ECHEC, 'Failed to authenticate.', 401);
    }

    /**
    * @OA\Get(
    *     path="/api/v1/profile",
    *     operationId="getProfile",
    *     tags={"Profile"},
    *     summary="Get user profile",
    *     description="Récuperer les informations de l'utilisateur connecté.",
    *     security={{"bearerAuth":{}}},
    *     @OA\Response(
    *         response=200,
    *         description="Opération réussie",
    *         @OA\JsonContent()
    *     ),
    *     @OA\Response(
    *         response=401,
    *         description="Unauthorized"
    *     )
    * )
    *
    * @OA\SecurityScheme(
    *     securityScheme="bearerAuth",
    *     type="http",
    *     scheme="bearer",
    *     bearerFormat="JWT"
    * )
    */
    public function profile(){
        $user = Auth::user()->load('role');
        if (!$user) {
            return $this->sendResponse(null, StatusResponseEnum::ECHEC, 'Token not provided', 401);
        }
        return $this->sendResponse(new UserResource($user), StatusResponseEnum::SUCCESS, 'Opération reussie', 200);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/logout",
     *     operationId="Logout",
     *     tags={"Logout"},
     *     summary="Logout user",
     *     description="Déconnexion de l'utilisateur de l'application.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Opération réussie",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function logout(){
        $this->authService->logout();
        return $this->sendResponse(null, StatusResponseEnum::SUCCESS, 'Vous avez été deconnecté avec succès');
    }


    public function index()
    {
        $users = User::all();
        return $this->sendResponse(UserResource::collection($users), StatusResponseEnum::SUCCESS);
    }

    public function store(Request $request)
    {
        try {
            $user = User::create([
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'photo' => $request->photo,
                'login' => $request->login,
                'password' => Hash::make($request->password),
                'role_id' => $request->role_id,
                'active' => $request->active,
            ]);

            return $this->sendResponse(new UserResource($user), StatusResponseEnum::SUCCESS);
        } catch (Exception $e) {
            return $this->sendResponse(['error' => $e->getMessage()], StatusResponseEnum::ECHEC, 500);
        }
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->sendResponse(['error' => 'User not found'], StatusResponseEnum::ECHEC, 404);
        }

        return $this->sendResponse(new UserResource($user), StatusResponseEnum::SUCCESS);
    }

    /*public function update(UpdateUserRequest $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->update($request->all());

            return $this->sendResponse(new UserResource($user), \App\Enums\StateEnum::SUCCESS);
        } catch (Exception $e) {
            return $this->sendResponse(['error' => $e->getMessage()], \App\Enums\StateEnum::ECHEC, 500);
        }
    }*/

    /*public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->sendResponse(['error' => 'User not found'], \App\Enums\StateEnum::ECHEC, 404);
        }

        $user->delete();
        return $this->sendResponse(null, \App\Enums\StateEnum::SUCCESS);
    }*/
}
