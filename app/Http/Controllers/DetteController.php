<?php

namespace App\Http\Controllers;

use App\Http\Requests\DetteRequest;
use App\Http\Requests\PaiementRequest;
use App\Models\Dette;
use App\Services\DetteServiceInterface;
use Illuminate\Http\Request;

class DetteController extends Controller
{

    private DetteServiceInterface $detteService;
    public function __construct(DetteServiceInterface $detteService){
        $this->detteService = $detteService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/demandes",
     *     operationId="CreateDetteDemande",
     *     tags={"DemandesDeDette"},
     *     summary="Créer une nouvelle demande de dette pour un client",
     *     description="Permet à un client authentifié de créer une demande de dette en fournissant des détails sur les articles demandés. Le montant total est calculé en fonction des articles et de leurs quantités. Des restrictions sont appliquées selon la catégorie du client (Silver, Bronze).",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"details_article_demande"},
     *                 @OA\Property(
     *                     property="details_article_demande",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="articleId", type="integer", example=1, description="L'ID de l'article à ajouter à la demande."),
     *                         @OA\Property(property="qteDemande", type="integer", example=2, description="La quantité demandée de cet article.")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Demande créée avec succès.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=10, description="L'ID de la demande créée."),
     *             @OA\Property(property="client_id", type="integer", example=1, description="L'ID du client ayant effectué la demande."),
     *             @OA\Property(property="montant", type="number", format="float", example=2500.0, description="Le montant total de la demande."),
     *             @OA\Property(
     *                 property="articles",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="articleId", type="integer", example=1),
     *                     @OA\Property(property="qteDemande", type="integer", example=2)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Requête invalide - Le client ou les détails de la demande sont incorrects.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Client non trouvé.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé - Les restrictions du client empêchent la création de la demande.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Vous ne pouvez pas faire de demande dette car votre montant maximum de dette est atteint.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Aucun article ajouté - La demande ne peut pas être créée.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Impossible de creer cette demande car aucun article n'a pu etre ajouter.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur interne lors de la création de la demande.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Erreur lors de la creation de la demande.")
     *         )
     *     )
     * )
     */
    public function store(DetteRequest $request){
        $detteRequest = $request->only("client", "details_dette", "paiement");
        $dette = $this->detteService->create($detteRequest);
        return $dette;
    }

    public function index(Request $request){
        return $this->detteService->filter($request);
    }

    public function paiement($id, PaiementRequest $request){
        return $this->detteService->paiement($id, $request);
    }

    public function getById($id){
        $dette = $this->detteService->find($id);

        if(!$dette){
            return response()->json(["message" => "La dette avec l'id " . $id . " n'existe pas."], 404);
        }else{
            return $dette;
        }
    }

    public function settledDebts(){
        
        $dettes = Dette::with(['paiements', 'client', 'articles'])->where('statut', 'solde')->get();
        return response()->json($dettes);
    }

    public function debtsDetails($id){
        $dette = $this->detteService->detbsWithDetails($id);

        if(!$dette){
            return response()->json(["message" => "La dette avec l'id " . $id . " n'existe pas."], 404);
        }else{
            return $dette;
        }
    }

    public function debtsWithAllpayments($id){
        $dette = $this->detteService->debtsWithPayments($id);

        if(!$dette){
            return response()->json(["message" => "La dette avec l'id " . $id . " n'existe pas."], 404);
        }else{
            return $dette;
        }
    }
}
