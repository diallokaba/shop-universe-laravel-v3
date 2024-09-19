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
     *     path="/api/v1/dettes",
     *     operationId="createDette",
     *     tags={"Dettes"},
     *     summary="Créer une nouvelle dette pour un client",
     *     description="Permet de créer une nouvelle dette pour un client spécifié, incluant les articles de la dette et un paiement initial optionnel.",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"client", "details_dette"},
     *                 @OA\Property(
     *                     property="client",
     *                     type="object",
     *                     required={"id"},
     *                     @OA\Property(property="id", type="integer", example=1, description="L'ID du client lié à la dette.")
     *                 ),
     *                 @OA\Property(
     *                     property="details_dette",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         required={"articleId", "qteVente", "prixVente"},
     *                         @OA\Property(property="articleId", type="integer", example=2, description="L'ID de l'article."),
     *                         @OA\Property(property="qteVente", type="integer", example=3, description="La quantité vendue."),
     *                         @OA\Property(property="prixVente", type="number", format="float", example=1500.50, description="Le prix de vente de l'article.")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="echeance",
     *                     type="string",
     *                     format="date",
     *                     example="2024-12-31",
     *                     description="La date d'échéance de la dette (optionnelle)."
     *                 ),
     *                 @OA\Property(
     *                     property="paiement",
     *                     type="object",
     *                     @OA\Property(property="montant", type="number", format="float", example=500.00, description="Le montant du premier paiement (optionnel).")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dette créée avec succès.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="dette", type="object", description="Informations sur la dette créée.",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="client_id", type="integer", example=1),
     *                 @OA\Property(property="montant", type="number", format="float", example=4500.0)
     *             ),
     *             @OA\Property(
     *                 property="success",
     *                 type="array",
     *                 @OA\Items(type="integer", example=2, description="ID des articles ajoutés avec succès.")
     *             ),
     *             @OA\Property(
     *                 property="failed",
     *                 type="array",
     *                 @OA\Items(type="integer", example=3, description="ID des articles qui n'ont pas pu être ajoutés.")
     *             ),
     *             @OA\Property(
     *                 property="paiement",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="dette_id", type="integer", example=10),
     *                 @OA\Property(property="montant", type="number", format="float", example=500.0)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Requête invalide - Données de la requête incorrectes ou incomplètes.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Erreur lors de la création de la dette. La dette n'a pas pu être créée.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity - Erreur de logique métier, par exemple un paiement initial supérieur au montant total de la dette.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Le montant du premier paiement doit être inférieur au montant total de la dette.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur interne lors de la création de la dette.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Erreur lors de la création de la dette : Erreur spécifique.")
     *         )
     *     )
     * )
     */
    public function store(DetteRequest $request){
        $detteRequest = $request->only("client", "details_dette", "paiement");
        $dette = $this->detteService->create($detteRequest);
        return $dette;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dettes",
     *     operationId="getDettes",
     *     tags={"Dettes"},
     *     summary="Obtenir une liste de dettes filtrée par statut",
     *     description="Retourne une liste de dettes, avec la possibilité de filtrer par statut (solde ou nonsolde). Si aucun statut n'est spécifié, retourne toutes les dettes.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer les dettes par statut. Peut être 'solde' ou 'nonsolde'.",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             example="solde"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des dettes récupérées avec succès.",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="client_id", type="integer", example=1),
     *                 @OA\Property(property="montant", type="number", format="float", example=1500.0),
     *                 @OA\Property(property="statut", type="string", example="solde", description="Le statut de la dette."),
     *                 @OA\Property(
     *                     property="client",
     *                     type="object",
     *                     description="Informations sur le client associé à la dette.",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nom", type="string", example="John Doe")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Requête invalide - Paramètres de requête incorrects ou manquants.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Paramètre 'statut' invalide.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur interne lors de la récupération des dettes.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Erreur lors de l'affichage des dettes : Erreur spécifique.")
     *         )
     *     )
     * )
     */
    public function index(Request $request){
        return $this->detteService->filter($request);
    }


    /**
     * @OA\Post(
     *     path="/api/v1/dettes/{id}/paiments",
     *     operationId="paiement",
     *     tags={"Paiements"},
     *     summary="Effectuer un paiement pour une dette",
     *     description="Permet d'effectuer un paiement pour une dette existante en spécifiant l'ID de la dette et le montant du paiement.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="L'ID de la dette à payer.",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"montant"},
     *                 @OA\Property(
     *                     property="montant",
     *                     type="number",
     *                     format="float",
     *                     example=500.00,
     *                     description="Le montant du paiement à effectuer."
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paiement effectué avec succès.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="dette",
     *                 type="object",
     *                 description="Informations sur la dette mise à jour.",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="client_id", type="integer", example=1),
     *                 @OA\Property(property="montant", type="number", format="float", example=1500.0),
     *                 @OA\Property(property="statut", type="string", example="solde", description="Le statut de la dette.")
     *             ),
     *             @OA\Property(
     *                 property="paiements",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="dette_id", type="integer", example=10),
     *                     @OA\Property(property="montant", type="number", format="float", example=500.0)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Requête invalide - Données de la requête incorrectes ou incomplètes.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="La dette avec l'id 10 n'existe pas.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity - Erreur de logique métier, par exemple un paiement total dépassant le montant de la dette.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Le montant total de paiement est supérieur au montant de la dette.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur interne lors de l'exécution du paiement.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Erreur lors de l'exécution du paiement : Erreur spécifique.")
     *         )
     *     )
     * )
     */
    public function paiement($id, PaiementRequest $request){
        $paiement = $this->detteService->paiement($id, $request);
        return response()->json(["data" => $paiement], 200);
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

    /**
     * @OA\Get(
     *     path="/api/v1/dettes/{id}/paiments",
     *     operationId="getDebtsWithPayments",
     *     tags={"Dettes"},
     *     summary="Obtenir les détails d'une dette avec tous les paiements associés",
     *     description="Retourne les informations sur une dette spécifique ainsi que tous les paiements effectués pour cette dette, en utilisant l'ID de la dette.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="L'ID de la dette pour laquelle récupérer les informations et paiements.",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la dette et des paiements récupérés avec succès.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="dette",
     *                 type="object",
     *                 description="Informations sur la dette.",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="client_id", type="integer", example=1),
     *                 @OA\Property(property="montant", type="number", format="float", example=1500.0),
     *                 @OA\Property(property="statut", type="string", example="solde", description="Le statut de la dette.")
     *             ),
     *             @OA\Property(
     *                 property="paiements",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="dette_id", type="integer", example=10),
     *                     @OA\Property(property="montant", type="number", format="float", example=500.0)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="La dette avec l'ID spécifié n'existe pas.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="La dette avec l'id 10 n'existe pas.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur interne lors de la récupération des informations de la dette.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Erreur lors de la récupération des paiements : Erreur spécifique.")
     *         )
     *     )
     * )
     */
    public function debtsWithAllpayments($id){
        $dette = $this->detteService->debtsWithPayments($id);

        if(!$dette){
            return response()->json(["message" => "La dette avec l'id " . $id . " n'existe pas."], 404);
        }

        return response()->json(["data" => $dette], 200);
    }
}
