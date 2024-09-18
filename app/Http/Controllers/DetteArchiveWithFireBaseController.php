<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Facades\FirebaseClientFacade;
use App\Models\Dette;
use App\Models\Paiement;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class DetteArchiveWithFireBaseController extends Controller
{
    public function archiveDette(Request $request)
    {
        try {
            $data = [];

            if ($request->has('date')) {
                $date = $request->get('date');
                $collectionName = 'archives/' . $date;

                $archiveDebts = FirebaseClientFacade::getCollection($collectionName);

                if (empty($archiveDebts)) {
                    return response()->json(['data' => []], 200);
                }

                foreach ($archiveDebts as $key => $dette) {
                    $data[] = [
                        'collectionName' => $collectionName,
                        'dette' => $dette['dette'],
                    ];
                }
            } elseif ($request->has('telephone')) {
                $telephone = $request->get('telephone');
                $dateNow = Carbon::now()->format('Y_m_d');

                for ($i = 0; $i < 30; $i++) { // Vérifie les archives des 30 derniers jours
                    $collectionName = 'archives/' . $dateNow;
                    $archiveDebts = FirebaseClientFacade::getCollection($collectionName);

                    if (!empty($archiveDebts)) {
                        foreach ($archiveDebts as $key => $dette) {
                            if ($dette['dette']['client']['telephone'] == $telephone) {
                                $data[] = [
                                    'collectionName' => $collectionName,
                                    'dette' => $dette['dette'],
                                ];
                            }
                        }
                    }

                    // Passe au jour précédent
                    $dateNow = Carbon::now()->subDays($i + 1)->format('Y_m_d');
                }
            } else {
                $dateNow = Carbon::now()->format('Y_m_d');

                for ($i = 0; $i < 30; $i++) { // Vérifie les archives des 30 derniers jours
                    $collectionName = 'archives/' . $dateNow;
                    $archiveDebts = FirebaseClientFacade::getCollection($collectionName);

                    if (!empty($archiveDebts)) {
                        foreach ($archiveDebts as $key => $dette) {
                            $data[] = [
                                'collectionName' => $collectionName,
                                'dette' => $dette['dette'],
                            ];
                        }
                    }

                    // Passe au jour précédent
                    $dateNow = Carbon::now()->subDays($i + 1)->format('Y_m_d');
                }
            }

            return response()->json(['data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erreur lors de la récupération des dettes archivées : ' . $e->getMessage()], 500);
        }
    }

    // Endpoint pour récupérer les dettes archivées d'un client à travers le client_id
    public function archiveClientDebts($id)
    {
        try {
            $data = [];
            $id = (int)$id;
            $dateNow = Carbon::now()->format('Y_m_d');

            for ($i = 0; $i < 30; $i++) { // Vérifie les archives des 30 derniers jours
                $collectionName = 'archives/' . $dateNow;
                $archiveDebts = FirebaseClientFacade::getCollection($collectionName);

                if (!empty($archiveDebts)) {
                    foreach ($archiveDebts as $key => $dette) {
                        if ($dette['dette']['client_id'] == $id) {
                            $data[] = [
                                'collectionName' => $collectionName,
                                'dette' => $dette['dette'],
                            ];
                        }
                    }
                }

                // Passe au jour précédent
                $dateNow = Carbon::now()->subDays($i + 1)->format('Y_m_d');
            }

            return response()->json(['data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erreur lors de la récupération des dettes archivées de ce client : ' . $e->getMessage()], 500);
        }
    }

    // Endpoint pour récupérer une dette à travers son id
    public function archiveById($id)
    {
        try {
            $data = null;
            $id = (int)$id;
            $dateNow = Carbon::now()->format('Y_m_d');

            for ($i = 0; $i < 30; $i++) { // Vérifie les archives des 30 derniers jours
                $collectionName = 'archives/' . $dateNow;
                $archiveDebts = FirebaseClientFacade::getCollection($collectionName);

                if (!empty($archiveDebts)) {
                    foreach ($archiveDebts as $key => $dette) {
                        if ($dette['dette']['id'] == $id) {
                            $data = [
                                'collectionName' => $collectionName,
                                'dette' => $dette['dette'],
                            ];
                            break 2; // Sortir des deux boucles
                        }
                    }
                }

                // Passe au jour précédent
                $dateNow = Carbon::now()->subDays($i + 1)->format('Y_m_d');
            }

            return response()->json(['data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Erreur lors de la récupération de la dette archivée : ' . $e->getMessage()], 500);
        }
    }

    public function restaureDataFromOnlineByDate($date)
{
    try {
        DB::beginTransaction(); // Démarrer une transaction pour assurer l'intégrité des données

        $collectionName = 'archives/' . $date;
        $archiveDebts = FirebaseClientFacade::getCollection($collectionName);

        if (empty($archiveDebts)) {
            return response()->json(['message' => 'Aucune donnée à restaurer'], 200);
        }

        foreach ($archiveDebts as $key => $archive) {
            $dette = Dette::create([
                'montant' => $archive['dette']['montant'],
                'client_id' => $archive['dette']['client_id'],
                'statut' => $archive['dette']['statut'],
                'created_at' => $archive['dette']['created_at'],
                'updated_at' => now()
            ]);

            foreach ($archive['dette']['paiements'] as $paiement) {
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

        // Supprimer la collection de dettes dans Firebase
        FirebaseClientFacade::deleteCollection($collectionName);

        DB::commit(); // Valider la transaction

        return response()->json(['message' => 'Données restaurées avec succès'], 200);
    } catch (Exception $e) {
        DB::rollBack();
        return response()->json(['error' => 'Erreur lors de la restauration des données : ' . $e->getMessage()], 500);
    }
}

public function restaureDataFromOnlineById($id)
{
    try {
        DB::beginTransaction(); // Démarrer une transaction pour assurer l'intégrité des données

        $id = (int) $id;
        $isFind = false;
        $dateNow = Carbon::now()->format('Y_m_d');

        for ($i = 0; $i < 30; $i++) { // Vérifie les archives des 30 derniers jours
            $collectionName = 'archives/' . $dateNow;
            $archiveDebts = FirebaseClientFacade::getCollection($collectionName);

            foreach ($archiveDebts as $key => $archive) {
                if ($archive['dette']['id'] == $id) {
                    $isFind = true;

                    $dette = Dette::create([
                        'montant' => $archive['dette']['montant'],
                        'client_id' => $archive['dette']['client_id'],
                        'statut' => $archive['dette']['statut'],
                        'created_at' => $archive['dette']['created_at'],
                        'updated_at' => now()
                    ]);

                    foreach ($archive['dette']['paiements'] as $paiement) {
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

                    // Supprimer la dette spécifique de la collection dans Firebase
                    FirebaseClientFacade::deleteDocument($collectionName, $key);

                    break 2; // Quitter les deux boucles dès que la dette est trouvée et traitée
                }
            }

            // Passe au jour précédent
            $dateNow = Carbon::now()->subDays($i + 1)->format('Y_m_d');
        }

        if (!$isFind) {
            return response()->json(['message' => 'Aucune dette trouvée avec l\'id ' . $id], 404);
        }

        DB::commit(); // Valider la transaction

        return response()->json(['message' => 'Données restaurées avec succès'], 200);
    } catch (Exception $e) {
        DB::rollBack();
        return response()->json(['error' => 'Erreur lors de la restauration des données : ' . $e->getMessage()], 500);
    }
}

public function restaureClientDebtByClientId($id)
{
    try {
        DB::beginTransaction(); // Démarrer une transaction pour assurer l'intégrité des données

        $id = (int) $id;
        $dateNow = Carbon::now()->format('Y_m_d');

        for ($i = 0; $i < 30; $i++) { // Vérifie les archives des 30 derniers jours
            $collectionName = 'archives/' . $dateNow;
            $archiveDebts = FirebaseClientFacade::getCollection($collectionName);

            foreach ($archiveDebts as $key => $archive) {
                if ($archive['dette']['client_id'] == $id) {
                    $dette = Dette::create([
                        'montant' => $archive['dette']['montant'],
                        'client_id' => $archive['dette']['client_id'],
                        'statut' => $archive['dette']['statut'],
                        'created_at' => $archive['dette']['created_at'],
                        'updated_at' => now()
                    ]);

                    foreach ($archive['dette']['paiements'] as $paiement) {
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

                    // Supprimer la dette spécifique de la collection dans Firebase
                    FirebaseClientFacade::deleteDocument($collectionName, $key);
                }
            }

            // Passe au jour précédent
            $dateNow = Carbon::now()->subDays($i + 1)->format('Y_m_d');
        }

        DB::commit(); // Valider la transaction

        return response()->json(['message' => 'Données restaurées avec succès'], 200);
    } catch (Exception $e) {
        DB::rollBack();
        return response()->json(['error' => 'Erreur lors de la restauration des données : ' . $e->getMessage()], 500);
    }
}


}

