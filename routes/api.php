<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DemandeController;
use App\Http\Controllers\DetteArchiveController;
use App\Http\Controllers\DetteController;
use App\Http\Controllers\NotificationClientController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

//open routes
Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});


//protected routes
//protected routes
Route::group(['middleware' => ['auth:api'], 'prefix' => 'v1'], function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/logout', [AuthController::class, 'logout']);
});

//protected route only ADMIN can access
Route::group([
    'middleware' => ['auth:api', 'checkRole:1'],
    'prefix' => 'v1', // Ajout du préfixe global 'v1'
], function () {
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users', [UserController::class, 'index']);

    Route::get('/dettes/settled/all', [DetteController::class, 'settledDebts']);

    Route::get('/dettes/archive', [DetteArchiveController::class, 'archiveDette']);
    Route::get('/archive/clients/{id}/dettes', [DetteArchiveController::class, 'archiveClientDebts']);
    Route::get('/archive/dettes/{id}', [DetteArchiveController::class, 'archiveById']);
    Route::get('/restaure/{date}', [DetteArchiveController::class, 'restaureDataFromOnlineByDate']);
    Route::get('/restaure-one/{id}', [DetteArchiveController::class, 'restaureDataFromOnlineById']);
    Route::get('/restaure/dette/client/{id}', [DetteArchiveController::class, 'restaureClientDebtByClientId']);
});

//protected route only shopkeeper cas access
Route::group([
    'middleware' => ['auth:api', 'checkRole:2'],
    'prefix' => 'v1', // Ajout du préfixe global 'v1'
], function () {

    Route::patch('/articles/{id}', [ArticleController::class, 'updateStockById']);
    Route::post('/articles/stock', [ArticleController::class, 'updateStock']);
    Route::get('/articles', [ArticleController::class, 'allWithFilterStock']);
    Route::get('/articles/all', [ArticleController::class, 'index']);
    Route::get('/articles/{id}', [ArticleController::class, 'getArticleById']);
    Route::post('/articles/libelle', [ArticleController::class, 'getArticleByLibelle']);
    Route::post('/articles', [ArticleController::class, 'store']);

    Route::post('/dettes', [DetteController::class, 'store']);
    Route::get('/dettes', [DetteController::class, 'index']);
    Route::get('/dettes/{id}', [DetteController::class, 'getById']);
    Route::post('/dettes/{id}/paiments', [DetteController::class, 'paiement']);

    Route::get('/notification/client/{id}', [NotificationClientController::class, 'sendRemainderNotificationForSettledDebt']);
    Route::post('/notification/client/all', [NotificationClientController::class, 'sendRemainderNotificationForManyClient']);
    Route::post('/notification/client/message', [NotificationClientController::class, 'sendRemainderSMSNotification']);

    //Demande
    Route::get('/demandes/{id}/disponible', [DemandeController::class, 'verifyDisponibiliteAndSendNotificationOrNot']);
    Route::patch('/demandes/{id}', [DemandeController::class, 'validateOrCancelDemande']);

    Route::get('/demandes/all', [DemandeController::class, 'allDemandeWithouSpecificUser']);
    Route::get('/demandes/notifications', [DemandeController::class, 'showDebtsNotification']);
});


//protected route. Shopkeeper and Client can access
Route::group([
    'middleware' => ['auth:api',  'checkRole:2,3'],
    'prefix' => 'v1', // Ajout du préfixe global 'v1'
], function () {
    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::post('/clients/telephone', [ClientController::class, 'getByTelephone']);
    Route::get('/clients/{id}', [ClientController::class, 'getById']);
    Route::get('/clients/{id}/dettes', [ClientController::class, 'debtsByClientId']);
    Route::get('/clients/{id}/user', [ClientController::class, 'clientWithHisAccount']);

    Route::get('/dettes/{id}/articles', [DetteController::class, 'debtsDetails']);
    Route::get('/dettes/{id}/paiments', [DetteController::class, 'debtsWithAllpayments']);
});


Route::group([
    'middleware' => ['auth:api',  'checkRole:3'],
    'prefix' => 'v1', // Ajout du préfixe global 'v1'
], function () {
    Route::get('/clients/notifications', [NotificationClientController::class, 'notifications']);

    //Demande
    Route::post('/demandes', [DemandeController::class, 'store']);
    Route::get('/demandes', [DemandeController::class, 'allWithFilter']);

    Route::post('/demandes/{id}/relance', [DemandeController::class, 'relanceDemande']);
    Route::post('/demandes/notifications/client', [DemandeController::class, 'showClientNotification']);
});