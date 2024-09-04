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

//route public

Route::post('/registre', [AuthController::class, 'registre'])->withoutMiddleware(JWTMiddleware::class);
Route::post('/login', [AuthController::class, 'login'])->withoutMiddleware(JWTMiddleware::class);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->withoutMiddleware(JWTMiddleware::class);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->withoutMiddleware(JWTMiddleware::class);

Route::get('/reset-password/{token}', function ($token) {
})->name('password.reset');
Route::get('/messages/boite-messagerie', [MessageEnvoyerController::class, 'viewUserMessages']);

      Route::get('/messages/clients', [MessageEnvoyerController::class, 'listClients']);
      Route::get('/messages/admins', [MessageEnvoyerController::class, 'listAdmins']);
      Route::get('/messages/unread', [MessageEnvoyerController::class, 'listUnreadMessages']);
      Route::get('/messages/read', [MessageEnvoyerController::class, 'listReadMessages']);

Route::get('/sous-categories/{id}', [SousCategorieController::class, 'show']);
Route::get('/sous-categories', [SousCategorieController::class, 'index']);
Route::get('/genres/{id}', [GenresController::class, 'show']);
Route::get('/genres', [GenresController::class, 'index']);
Route::get('categories', [CategoriesController::class, 'index']);
Route::get('categories/{id}', [CategoriesController::class, 'show']);

Route::get('/produits/produits/{id}', [ProduitsController::class, 'getProduitById']);
Route::get('/produits/genre/{genreId}', [ProduitsController::class, 'produitsParGenre']);
Route::get('/produits/recherche', [ProduitsController::class, 'index']);

Route::get('/produits/accueil', [ProduitsController::class, 'afficherTousLesProduitspageclient']);
Route::get('/produits/categorie/{categorieId}', [ProduitsController::class, 'produitParCategorie']);
Route::get('/produits/produitsParMotCle', [ProduitsController::class, 'produitsParMotCle']);
Route::get('/produits/nouveaux-produits', [ProduitsController::class, 'nouveauxProduits']);
Route::get('/produits/genre/{genreId}/categorie/{categorieId}', [ProduitsController::class, 'produitsParGenreEtCategorie']);
Route::get('/produits/sous-categorie/{sousCategorieId}', [ProduitsController::class, 'searchBySousCategorie']);
Route::get('/produits/promotions', [ProduitsController::class, 'produitsEnPromotions']);
Route::get('/produits/les-plus-commandes', [ProduitsController::class, 'produitsLesPlusCommandes']);



Route::get('/publicites/{id}', [PublicitesController::class, 'show']);

Route::get('/publicites', [PublicitesController::class, 'index']);

//route prive quote client ;

Route::group([

    'middleware' => JWTMiddleware::class,
    'prefix' => 'auth'

], function () {

    Route::middleware('auth:api')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('consulter-coordonnees', [AuthController::class, 'consulterCoordonnees']);
    Route::post('modifier-coordonnees', [AuthController::class, 'modifierCoordonnees']);
    Route::get('historiquedachat', [AuthController::class, 'historiquedachat']);


    //paniercontroller
    Route::get('/panier/consulterPanier', [PaniersController::class, 'consulterPanier']);

    Route::post('/panier/ajouter/{produitId}', [PaniersController::class, 'ajouterAuPaniers']);
    Route::post('/panier/mettre-a-jour/{produitId}', [PaniersController::class, 'mettreAJourPanier']);
    Route::delete('/panier/retirer/{produitId}', [PaniersController::class, 'retirerDuPanier']);


//commandes controller
    Route::get('/fraislivraison', [CommandesController::class, 'obtenirFraisLivraison']);

    Route::post('/commander', [CommandesController::class, 'commander']);
    Route::get('/commandes/detailscommande', [CommandesController::class, 'voirDetailsCommande']);

//message controller
    Route::get('/messages/{id}', [MessageEnvoyerController::class, 'showMessage']);
    Route::post('/messages/{idMessage}/reply', [MessageEnvoyerController::class, 'replyToMessage']);
   // Route::delete('/messages/{id}', [MessageEnvoyerController::class, 'deleteMessage']);
    Route::post('/messages/contact-admin', [MessageEnvoyerController::class, 'contactAdmin']);


});





//cote admin

Route::middleware('auth:api')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'stats']);
});

Route::middleware('auth:api')->group(function () {

      Route::post('/categories', [CategoriesController::class, 'store']);
      Route::put('/categories/{id}', [CategoriesController::class, 'update']);
      Route::delete('/categories/{id}', [CategoriesController::class, 'destroy']);

      Route::post('/genres', [GenresController::class, 'store']);
      Route::put('/genres/{id}', [GenresController::class, 'update']);
      Route::delete('/genres/{id}', [GenresController::class, 'destroy']);

      Route::post('/sous-categories', [SousCategorieController::class, 'store']);
      Route::put('/sous-categories/{id}', [SousCategorieController::class, 'update']);
      Route::delete('/sous-categories/{id}', [SousCategorieController::class, 'destroy']);

      Route::post('/produits/{idProduit}/promos', [ProduitsController::class, 'ajouterPromos']);
      Route::post('/produits/{idProduit}/promos/{idPromos}', [ProduitsController::class, 'updatePromos']);
      //Route::post('/produits/{idProduit}/promos/update-or-create', [ProduitsController::class, 'updateOrCreatePromos']);
      Route::delete('/produits/{idProduit}/promos', [ProduitsController::class, 'removePromos']);
      Route::post('/produits/promos/apply-to-multiple', [ProduitsController::class, 'applyPromosToMultipleProducts']);
      Route::get('/produits/afficherTousLesProduits', [ProduitsController::class, 'afficherTousLesProduits']);
      Route::post('/produits/{idProduit}/feature', [ProduitsController::class, 'featureProduct']);
      Route::post('/produits/{idProduit}/unfeature', [ProduitsController::class, 'unfeatureProduct']);
      Route::post('/produits/{idProduit}/hide', [ProduitsController::class, 'hideProduct']);
      Route::post('/produits/{idProduit}/unhide', [ProduitsController::class, 'unhideProduct']);
      Route::post('/produits/nouveauproduit', [ProduitsController::class, 'nouveauProduit']);


      Route::Post('/produits/modifierleproduit/{id}', [ProduitsController::class, 'modifierProduit']);
      Route::delete('/produits/supprimerProduit/{id}', [ProduitsController::class, 'supprimerProduit']);

      Route::post('/fraislivraison/update', [CommandesController::class, 'updatefraislivraison']);
      Route::post('/admin/commandes/{commandeId}/statut', [CommandesController::class, 'changerStatutCommande']);

      Route::get('/exportcommandespdf/{id}', [CommandesController::class, 'exporterCommandePDF']);

      Route::get('/export-commandes', [CommandesController::class, 'exporterCommandes']);
      Route::get('/commandes/{commandeId}/details', [CommandesController::class, 'voirDetailsCommandepouradmin']);
      Route::get('/commandes/details', [CommandesController::class, 'detailsCommandes']);



      //Route::get('/messages/clients', [MessageEnvoyerController::class, 'listClients']);
      //Route::get('/messages/admins', [MessageEnvoyerController::class, 'listAdmins']);
        Route::get('/messages', [MessageEnvoyerController::class, 'listMessages']);
       // Route::get('/messages/unread', [MessageEnvoyerController::class, 'listUnreadMessages']);
       // Route::get('/messages/read', [MessageEnvoyerController::class, 'listReadMessages']);
        Route::post('/messages/search', [MessageEnvoyerController::class, 'searchMessages']);
        Route::post('/messages/send-to-client/{id}', [MessageEnvoyerController::class, 'sendMessageToClient']);
        Route::post('/users/{userId}/block', [MessageEnvoyerController::class, 'blockUser']);
        Route::post('/users/{userId}/unblock', [MessageEnvoyerController::class, 'unblockUser']);





        Route::post('/publicites/update/{id}', [PublicitesController::class, 'update']);
        Route::delete('/publicites/delete/{id}', [PublicitesController::class, 'destroy']);
        Route::post('/publicites/create', [PublicitesController::class, 'store']);


    });


//superadminauth

Route::group(['middleware' => ['auth']], function () {
   Route::post('/admin/create', [SuperAdminController::class, 'createadministrateur']);
    Route::post('/admin/update/{id}', [App\Http\Controllers\SuperAdminController::class, 'updateadmin']);
    Route::delete('/admin/delete/{id}', [SuperAdminController::class, 'deleteUser']);
    Route::get('/admin/show/{id}', [SuperAdminController::class, 'showadmin']);
    Route::post('/admin/search/username', [SuperAdminController::class, 'searchByUsernameadmin']);
    Route::post('/admin/research', [SuperAdminController::class, 'rechercheradmin']);
    Route::post('/admin/get-users-by-role', [SuperAdminController::class, 'getUsersByRole']);
    Route::get('/admin/get-admins', [SuperAdminController::class, 'getAdmins']);
    Route::patch('/admin/update-status/{id}', [SuperAdminController::class, 'updateAdminStatus']);

  Route::post('/client/create', [SuperAdminController::class, 'createclient']);
  Route::post('/client/update/{id}', [SuperAdminController::class, 'updateclient']);
  Route::delete('/client/delete/{id}', [SuperAdminController::class, 'deleteClient']);
  Route::get('/client/show/{id}', [SuperAdminController::class, 'showclient']);
  Route::post('/client/search/username', [SuperAdminController::class, 'searchByUsernameclient']);
  Route::post('/client/research', [SuperAdminController::class, 'rechercherclient']);
  Route::get('/client/get-clients', [SuperAdminController::class, 'getClients']);
  Route::patch('/client/update-status/{id}', [SuperAdminController::class, 'updateClientStatus']);

});




// Routes pour la gestion des promotions

//

// Route::get('/promos', [PromosController::class, 'index']);
// Route::post('/promos', [PromosController::class, 'store']);
// Route::get('/promos/{id}', [PromosController::class, 'show']);
// Route::put('/promos/{id}', [PromosController::class, 'update']);
// Route::delete('/promos/{id}', [PromosController::class, 'destroy']);
// //
//Route::get('/publicites', [PublicitesController::class, 'index']);
//Route::get('/publicites/{id}', [PublicitesController::class, 'show']);



});




