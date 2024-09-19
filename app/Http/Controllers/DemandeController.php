<?php

namespace App\Http\Controllers;

use App\Http\Requests\DemandeRequest;
use App\Models\Client;
use App\Models\Demande;
use App\Models\Dette;
use App\Notifications\DemandeNotification;
use App\Notifications\DemandeNotificationForQuantity;
use App\Services\DemandeServiceInterface;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DemandeController extends Controller
{

    private $demandeService;
    public function __construct(DemandeServiceInterface $demandeService){
        $this->demandeService = $demandeService;
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
    public function store(DemandeRequest $request){
        $demandeRequest = $request->only("details_article_demande");
        $demande = $this->demandeService->create($demandeRequest);
        return $demande;
    }

    public function allWithFilter(Request $request){
        $demande = $this->demandeService->getDemandeWithFilterPossibility($request);
        return response()->json(['data' => $demande], 200);
    }

    public function verifyDisponibiliteAndSendNotificationOrNot($id){
        $demande = Demande::with('articles')->find($id);

        if (!$demande) {
            return response()->json(['message' => 'Demande non trouvée.'], 404);
        }

        $articlesDisponibles = [];
        $articlesNonDisponibles = [];

        foreach ($demande->articles as $article) {
            $qteDemande = $article->pivot->qteDemande;

            // Vérifier si la quantité demandée peut être satisfaite sans dépasser le seuil de stock
            if ($article->quantite >= $qteDemande && $article->quantite > $article->seuil) {
                $articlesDisponibles[] = $article;
            } else {
                $articlesNonDisponibles[] = $article;
            }
        }

        if(empty($articlesDisponibles)){
            return response()->json([
               'message' => 'Aucun article disponible pour cette demande.',
                'articles_non_disponibles' => $articlesNonDisponibles
            ], 400);
        }

        
        if (!empty($articlesNonDisponibles)) {
            $client = $demande->client;
            $client->notify(new DemandeNotificationForQuantity($articlesDisponibles));
        }

        // Tous les articles sont disponibles
        return response()->json([
            'articles_disponibles' => $articlesDisponibles,
            'articles_non_disponibles' => $articlesNonDisponibles
        ], 200);
    }


    public function validateOrCancelDemande(Request $request, $id){

        $demande = Demande::with('articles')->find($id);

        if (!$demande) {
            return response()->json(['message' => 'Demande non trouvée.'], 404);
        }

        //Dans le body
        $statut = strtoupper($request->input('statut'));

        if(!$statut){
            return response()->json(['message' => 'Veuillez renseigné le statut de la demande.'], 400);
        }

        try {
            DB::beginTransaction();

            if ($statut === 'VALIDER') {
                $montantDette = 0;
                $clientId = $demande->client_id;
                // Enregistrement de la dette
                $dette = new Dette([
                    'client_id' => $clientId,
                    'montant' => $montantDette,
                ]);

                $dette->save();

                $articleSuccess = [];
                $articleFailed = [];

               foreach ($demande->articles as $article) {
                    $articleId = $article->id;
                    $qteVente = $article->pivot->qteDemande;
                    $prixVente = $article->prix;

                    // Vérifier la disponibilité de l'article
                    if ($articleId && $article->quantite >= $qteVente) {
                        // Mise à jour du stock de l'article
                        $article->quantite -= $qteVente;
                        $article->save();

                        // Calculer le montant total de la dette
                        $montantDette += $qteVente * $prixVente;

                        // Attacher l'article à la dette
                        $dette->articles()->attach($articleId, [
                            'qteVente' => $qteVente,
                            'prixVente' => $prixVente,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        $articleSuccess[] = $articleId;
                    } else {
                        $articleFailed[] = $articleId;
                    }
                }

                if (empty($articleSuccess)) {
                    DB::rollBack();
                    return response()->json(['message' => "Impossible de valider la demande car aucun article n'a pu être ajouté."], 400);
                }
    
                // Mise à jour du montant total de la dette
                $dette->montant = $montantDette;
                $dette->save();
    
                // Mettre à jour le statut de la demande à 'VALIDER'
                $demande->statut = 'VALIDER';
                $demande->save();
    
                // Envoyer une notification au client
                $demande->client->notify(new DemandeNotification('Votre demande a été validée. Veuillez passer prendre les produits au niveau de la boutique.'));

            }else if($statut === 'ANNULER') {
                $demande->statut = 'ANNULER';
                $demande->date_annulation = now();
                $demande->save();

                // Envoyer une notification au client sur l'annulation
                $motif = $request->get('motif', 'Aucun motif fourni');
                $demande->client->notify(new DemandeNotification("Votre demande a été annulée. Motif : $motif"));
            } else{
                return response()->json(['message' => 'Statut non valide. Utilisez "VALIDER" ou "ANNULER".'], 400);
            }

            DB::commit();
            return response()->json(['message' => "La demande a été mise à jour avec succès."], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la mise à jour de la demande: ' . $e->getMessage()], 500);
        }
    }

    public function relanceDemande(Request $request, $id){
        $demande = Demande::find($id);

        if (!$demande) {
            return response()->json(['message' => 'Demande non trouvée.'], 404);
        }

        if($demande->statut === 'VALIDER'){
            return response()->json(['message' => 'Cette demande est déjà validée.'], 400);
        }

        if($demande->statut === 'ENCOURS'){
            return response()->json(['message' => 'Cette demande est encours.'], 400);
        }

        if(!$request->has('message')){
            return response()->json(['message' => 'La clé message est obligatoire'], 400);
        }

        $message = $request->get('message');

        if(!$message){
            return response()->json(['message' => 'Pour envoyer une relance, veuillez fournir un message'], 400);
        }

        // si la date_d'annulation est supérieur à 2 jours il n'est plus possible d'envoyer une notification de rappel
        $dateAnnulation = $demande->date_annulation;

        if($dateAnnulation->diffInDays(now()) > 2){
            return response()->json(['message' => 'Impossible d\'envoyer une relance car la demande a été annulée depuis plus de 2 jours.'], 400);
        }

        try {
            // Récupérer les boutiquiers et envoyer les notifications
            $boutiquiers = Client::with('user')->where('role_id', 2)->get();
    
            foreach ($boutiquiers as $boutiquier) {
                $boutiquier->notify(new DemandeNotification($message));
            }
    
            return response()->json(['message' => 'La relance a été envoyée avec succès.'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Erreur lors de l\'envoi de la relance : ' . $e->getMessage()], 500);
        }
    }

    public function showClientNotification(){

        $user = auth()->user();
        $client = Client::where('user_id', $user->id)->first();

        if (!$client) {
            return response()->json(['message' => 'Client non trouvé.'], 404);
        }

        // Récupérer les notifications du client
        $notifications = $client->notifications()->orderBy('created_at', 'desc')->get();

        // Vérifier si le client a des notifications
        if ($notifications->isEmpty()) {
            return response()->json(['message' => 'Aucune notification trouvée.'], 404);
        }

        // Retourner les notifications dans la réponse JSON
        return response()->json(['notifications' => $notifications], 200);
    }

    public function allDemandeWithouSpecificUser(Request $request){
        // Récupérer les demandes du client
        $query = Demande::where('statut', 'ENCOURS')->query();

        // Appliquer le filtre par statut s'il est fourni dans la requête
        if ($request->has('statut')) {
            $statut = strtoupper($request->input('statut')); // Normaliser en majuscules
            if (in_array($statut, ['ANNULER', 'ENCOURS'])) {
                $query->where('statut', $statut);
            } else {
                return response()->json(['message' => 'Statut non valide. Utilisez "ANNULER" ou "ENCOURS".'], 400);
            }
        }

        // Exécuter la requête et récupérer les résultats
        $demandes = $query->get();

        return response()->json(['demandes' => $demandes], 200);
    }

    public function showDebtsNotification(){
        $client = Client::find(199);

        if (!$client) {
            return response()->json(['message' => 'Client non trouvé.'], 404);
        }

        $notifications = $client->notifications()->where('type', 'App\Notifications\DemandeNotification')->get();

        return response()->json(['notifications' => $notifications], 200);
    }

}
