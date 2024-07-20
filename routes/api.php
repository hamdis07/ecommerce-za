<?php
use App\Http\Middleware\JWTMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\SousCategorieController;
use App\Http\Controllers\GenresController;
use App\Http\Controllers\PromosController;
use App\Http\Controllers\ProduitsController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\MessageEnvoyerController;
use App\Http\Controllers\MessageriesController;
use App\Http\Controllers\PaniersController;
use App\Http\Controllers\CommandesController;
use App\Http\Controllers\PublicitesController;

use App\Http\Controllers\DashBoardController;



// Routes pour l'affichage des produits
Route::get('/produits/afficherTousLesProduits', [ProduitsController::class, 'afficherTousLesProduits']);
Route::get('/produits/categorie/{categorieId}', [ProduitsController::class, 'produitParCategorie']);
Route::get('/produits/genre/{genreId}', [ProduitsController::class, 'produitsParGenre']);
Route::get('/produits/genre/{genreId}/categorie/{categorieId}', [ProduitsController::class, 'produitsParGenreEtCategorie']);
Route::get('/nouveaux-produits', [ProduitsController::class, 'nouveauxProduits']);
Route::get('/produits/sous-categorie/{sousCategorieId}', [ProduitsController::class, 'searchBySousCategorie']);
Route::get('/produits/produitsParMotCle', [ProduitsController::class, 'produitsParMotCle']);
Route::get('/produits/recherche', [ProduitsController::class, 'index']);


Route::post('/registre', [AuthController::class, 'registre'])->withoutMiddleware(JWTMiddleware::class);
Route::post('/login', [AuthController::class, 'login'])->withoutMiddleware(JWTMiddleware::class);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->withoutMiddleware(JWTMiddleware::class);

//Route::post('nouveauproduit', [ProduitsController::class, 'nouveauProduit']);
//Route::post('/panier/ajouter/{produitId}', [PaniersController::class, 'ajouterAuPaniers']);

Route::group([

    'middleware' => JWTMiddleware::class,
    'prefix' => 'auth'

], function () {

    //Route::post('registre', [App\Http\Controllers\AuthController::class,'registre']);
    // Route::post(' forgotPassword', [App\Http\Controllers\AuthController::class,' forgotPassword']);

  //  Route::post('login', [App\Http\Controllers\AuthController::class,'login']);
    Route::post('logout',  [App\Http\Controllers\AuthController::class,'logout']);
    Route::post('refresh', [App\Http\Controllers\AuthController::class,'refresh']);
    Route::post('me',  [App\Http\Controllers\AuthController::class,'me']);

//coteé client

Route::post('/panier/ajouter/{produitId}', [PaniersController::class, 'ajouterAuPaniers']);
Route::put('/panier/mettre-a-jour/{produitId}', [PaniersController::class, 'mettreAJourPanier']);
Route::delete('/panier/retirer/{produitId}', [PaniersController::class, 'retirerDuPanier']);



Route::middleware('auth:api')->group(function () {
    Route::post('/admin/create', [SuperAdminController::class, 'createadministrateur']);

    Route::post('/commander', [CommandesController::class, 'commandi']);
   // Route::post('/passercommande', [CommandesController::class, 'passercommande']);

});
Route::middleware('auth:api')->group(function () {
   Route::post('/messages/store', [MessageriesController::class, 'store']);
});

//coté admin


Route::group(['middleware' => ['auth']], function () {
    Route::post('/admin/create', [SuperAdminController::class, 'createadministrateur']);
    Route::put('/admin/update/{id}', [App\Http\Controllers\SuperAdminController::class, 'updateadmin']);
    Route::delete('/admin/delete/{id}', [SuperAdminController::class, 'deleteUser']);
    Route::get('/admin/show/{id}', [SuperAdminController::class, 'showadmin']);
    Route::post('/admin/search/username', [SuperAdminController::class, 'searchByUsernameadmin']);
    Route::post('/admin/research', [SuperAdminController::class, 'rechercheradmin']);
    Route::post('/admin/get-users-by-role', [SuperAdminController::class, 'getUsersByRole']);
    Route::get('/admin/get-admins', [SuperAdminController::class, 'getAdmins']);
});



Route::get('/genres', [GenresController::class, 'index']);
Route::post('/genres', [GenresController::class, 'store']);
Route::get('/genres/{id}', [GenresController::class, 'show']);
Route::put('/genres/{id}', [GenresController::class, 'update']);
Route::delete('/genres/{id}', [GenresController::class, 'destroy']);

//
Route::get('/categories', [CategoriesController::class, 'index']);
    Route::post('/categories', [CategoriesController::class, 'store']);
    Route::get('/categories/{id}', [CategoriesController::class, 'show']);
    Route::put('/categories/{id}', [CategoriesController::class, 'update']);
    Route::delete('/categories/{id}', [CategoriesController::class, 'destroy']);
//
Route::get('/sous-categories', [SousCategorieController::class, 'index']);
Route::post('/sous-categories', [SousCategorieController::class, 'store']);
Route::get('/sous-categories/{id}', [SousCategorieController::class, 'show']);
Route::put('/sous-categories/{id}', [SousCategorieController::class, 'update']);
Route::delete('/sous-categories/{id}', [SousCategorieController::class, 'destroy']);

// Route::prefix('products')->group(function () {
    Route::post('nouveauproduit', [ProduitsController::class, 'nouveauProduit']);
    Route::put('modifierleproduit/{id}', [ProduitsController::class, 'modifierProduit']);
    Route::delete('/produits/{id}', [ProduitsController::class, 'supprimerProduit']);
// });
// Routes pour la gestion des promotions
Route::post('/produits/{idProduit}/promos', [ProduitsController::class, 'ajouterPromos']);
Route::put('/produits/{idProduit}/promos/{idPromos}', [ProduitsController::class, 'updatePromos']);
Route::post('/produits/{idProduit}/promos/update-or-create', [ProduitsController::class, 'updateOrCreatePromos']);
Route::delete('/produits/{idProduit}/promos', [ProduitsController::class, 'removePromos']);

//

Route::get('/promos', [PromosController::class, 'index']);
Route::post('/promos', [PromosController::class, 'store']);
Route::get('/promos/{id}', [PromosController::class, 'show']);
Route::put('/promos/{id}', [PromosController::class, 'update']);
Route::delete('/promos/{id}', [PromosController::class, 'destroy']);
//
Route::get('/publicites', [PublicitesController::class, 'index']);
Route::post('/publicites/create', [PublicitesController::class, 'store']);
Route::get('/publicites/{id}', [PublicitesController::class, 'show']);
Route::post('/publicites/update/{id}', [PublicitesController::class, 'update']);
Route::delete('/publicites/{id}', [PublicitesController::class, 'destroy']);

Route::middleware('auth')->group(function () {
    Route::get('/messages', [MessageEnvoyerController::class, 'listMessages']);
    Route::get('/messages/{id}', [MessageEnvoyerController::class, 'showMessage']);
    Route::post('/messages/reply/{idMessage}', [MessageEnvoyerController::class, 'replyToMessage']);
    Route::delete('/messages/{id}', [MessageEnvoyerController::class, 'deleteMessage']);
    Route::post('/users/block/{userId}', [MessageEnvoyerController::class, 'blockUser']);

});
Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

});
