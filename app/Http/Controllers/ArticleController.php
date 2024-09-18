<?php

namespace App\Http\Controllers;

use App\Enums\StatusResponseEnum;
use App\Http\Requests\ArticleRequest;
use App\Http\Requests\UpdateStockArticleRequest;
use App\Models\Article;
use App\Services\ArticleServiceInterface;
use App\Traits\RestResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArticleController extends Controller
{
    use RestResponseTrait;

    private $articleService;

    public function __construct(ArticleServiceInterface $articleService){
        $this->articleService = $articleService;
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/articles/{id}",
     *     operationId="UpdateArticleStockById",
     *     tags={"UpdateArticleStockById"},
     *     summary="Update article stock by id",
     *     description="Mettre à jour la quantité en stock d'un article par son id",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="L'id de l'article à mettre à jour",
     *         required=true,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"qteStock"},
     *                 @OA\Property(property="qteStock", type="integer", example="3"),
     *             )
     *         ),
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"qteStock"},
     *                 @OA\Property(property="qteStock", type="integer", example="3"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quantité en stock mise à jour avec succès",
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
    public function updateStockById(UpdateStockArticleRequest $request, $id)
    {
        $article = Article::find($id);

        if (!$article) {
            return $this->sendResponse(null, StatusResponseEnum::ECHEC, 'Article introuvable', 404);
        }

        $article->quantite += $request->input('qteStock');  
        $article->save();

        return $this->sendResponse($article, StatusResponseEnum::SUCCESS, 'Quantité en stock mise à jour avec succès', 200);
    }

    public function updateStock(Request $request){
        $articleData = $request->input('articles');

        $notFoundArticles = [];
        $updatedArticles = [];
        DB::beginTransaction();

        try {
            foreach ($articleData as $article) {
                $articleId = $article['id'];
                $quantity = $article['qteStock'];

                $existingArticle = Article::find($articleId);

                if ($existingArticle && $quantity > 0) {
                    $existingArticle->quantite += $quantity;
                    $existingArticle->save();

                    $updatedArticles[] = ['id' => $existingArticle->id, 'libelle' => $existingArticle->libelle, 'qteStock' => $existingArticle->quantite];
                } else {
                    $notFoundArticles[] = ['id' => $articleId, 'qteStock' => $quantity];
                }
            }

            if(empty($updatedArticles)){
                return $this->sendResponse(['error' => $notFoundArticles], StatusResponseEnum::ECHEC, 'aucun article n\'a été mis à jour', 400);
            }

            DB::commit();

            return $this->sendResponse([
                'success' => $updatedArticles,
                'error' => $notFoundArticles
            ], StatusResponseEnum::SUCCESS, 200);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendResponse(['error' => $e->getMessage()], StatusResponseEnum::ECHEC, 500);
        }
    }

    public function index(){
        $articles = $this->articleService->all();
        if(empty($articles)){
            return $this->sendResponse([], StatusResponseEnum::SUCCESS, 'Pas d\'articles', 200);
        }else{
            return $this->sendResponse($articles, StatusResponseEnum::SUCCESS, 'Liste des articles', 200);
        }
    }

    public function allWithFilterStock2(Request $request){
        $articles = Article::query();

        if ($request->has('disponible')) {
            $value = strtolower($request->get('disponible'));
            if ($value === 'oui') {
                $articles->where('quantite', '>', 0);
            } elseif ($value === 'non') {
                $articles->where('quantite', '=', 0);
            }
        }

        if(empty($articles)){
            return $this->sendResponse(null, StatusResponseEnum::SUCCESS, 'Pas d\'articles', 200);
        }

        return $this->sendResponse($articles->get(), StatusResponseEnum::SUCCESS, 'Liste des articles', 200);
     }

     public function allWithFilterStock(Request $request){
         $articles = Article::filter($request)->get();
         if($articles->isEmpty()){
             return $this->sendResponse([], StatusResponseEnum::SUCCESS, 'Pas d\'articles', 200);
         }
         return $this->sendResponse($articles, StatusResponseEnum::SUCCESS, 'Liste des articles', 200);
     }

     public function getArticleById($id){
        if (!is_numeric($id)) {
            return $this->sendResponse(null, StatusResponseEnum::ECHEC, 'L\'identifiant doit être un nombre valide.', 400);
        }

        // Récupérer le client par ID
        $article = Article::find($id);
        
        // Vérifier si le client existe
        if (!$article) {
            return $this->sendResponse(null, StatusResponseEnum::ECHEC, 'Article non trouvé.', 404);
        }

        // Retourner le client trouvé
        return $this->sendResponse($article, StatusResponseEnum::SUCCESS, 'Article trouvé avec succès.', 200);
    }

    public function getArticleByLibelle(Request $request){

        $request->validate(['libelle' => 'required|string']);

        // Recherche de l'article par libelle
        $libelle = $request->input('libelle');
        $article = Article::where('libelle', $libelle)->first();
        
        // Vérifier si le client existe
        if (!$article) {
            return $this->sendResponse(null, StatusResponseEnum::ECHEC, 'Article non trouvé.', 404);
        }

        // Retourner le client trouvé
        return $this->sendResponse($article, StatusResponseEnum::SUCCESS, 'Article trouvé avec succès.', 200);
    }

    public function store(ArticleRequest $request){
        try {
            $articleData = $request->only('libelle', 'reference', 'prix', 'quantite');
            $article = $this->articleService->create($articleData);
            return $this->sendResponse($article, StatusResponseEnum::SUCCESS, 'Article enregistre avec succès', 201);
        } catch (Exception $e) {
            return $this->sendResponse(['error' => $e->getMessage()], StatusResponseEnum::ECHEC, 500);

        }
    }
}
