<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
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

//protected route only shopkeeper cas access
Route::group([
    'middleware' => ['auth:api', 'checkRole:2'],
    'prefix' => 'v1', // Ajout du préfixe global 'v1'
], function () {
    Route::get('/clients', [ClientController::class, 'all']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::post('/clients/telephone', [ClientController::class, 'getByTelephone']);

    Route::patch('/articles/{id}', [ArticleController::class, 'updateStockById']);
    Route::post('/articles/stock', [ArticleController::class, 'updateStock']);
    Route::get('/articles', [ArticleController::class, 'allWithFilterStock']);
    Route::get('/articles/all', [ArticleController::class, 'index']);
    Route::get('/articles/{id}', [ArticleController::class, 'getArticleById']);
    Route::post('/articles/libelle', [ArticleController::class, 'getArticleByLibelle']);
    Route::post('/articles', [ArticleController::class, 'store']);
});

//protected route only ADMIN can access
Route::group([
    'middleware' => ['auth:api', 'checkRole:1'],
    'prefix' => 'v1', // Ajout du préfixe global 'v1'
], function () {
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users', [UserController::class, 'index']);
});

//protected route. Shopkeeper and Client can access
Route::group([
    'middleware' => ['auth:api',  'checkRole:2,3'],
    'prefix' => 'v1', // Ajout du préfixe global 'v1'
], function () {
    Route::get('/clients/{id}', [ClientController::class, 'getById']);
    Route::get('/clients/{id}/dettes', [UserController::class, 'listDettesByIdClient']);
    Route::get('/clients/{id}/user', [ClientController::class, 'clientWithUser']);
});