<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;
use App\Services\Contracts\DatabaseServiceInterface;
use Exception;

class FirebaseService
{
    protected $database;

    public function __construct()
    {
        $serviceAccountPath = storage_path('app/shop-universe-larvel-firebase-adminsdk-54w3n-8f8187537f.json');

        $firebase = (new Factory)
            ->withServiceAccount($serviceAccountPath)
            ->withDatabaseUri('https://shop-universe-larvel-default-rtdb.firebaseio.com/');

        $this->database = $firebase->createDatabase();
    }

    // Méthode privée pour obtenir une référence Firebase
    public function getReference(string $path){
        return $this->database->getReference($path);
    }

    // Méthode pour récupérer une collection (ici une référence dans Firebase Realtime Database)
    public function getCollection(string $collectionName)
    {
        return $this->getReference($collectionName)->getValue();
    }

    // Méthode pour récupérer un document spécifique (ici un chemin spécifique dans Firebase Realtime Database)
    public function getDocument(string $collectionName, string $documentId)
    {
        return $this->getReference("{$collectionName}/{$documentId}")->getValue();
    }

    // Méthode pour sauvegarder un document (ici pour insérer des données dans Firebase Realtime Database)
    public function upsertDocument(string $collectionName, string $documentId, array $data)
    {
        try{
            $reference = $this->getReference("{$collectionName}/{$documentId}");
            $reference->set($data);
        }catch(Exception $e){
            throw new Exception('Erreur lors de la sauvegarde du document : ' . $e->getMessage());
        }
    }
}