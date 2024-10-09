<?php

namespace App\Http\Controllers;

use App\Models\Produits;
use App\Models\Paniers;
use App\Models\Tailles;
use App\Models\Couleurs;
use App\Models\Quantitedisponible;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaniersController extends Controller
{

    public function consulterPanier()
    {
        // Vérification si l'utilisateur est connecté
        if (!Auth::check()) {
            return response()->json(['message' => 'Vous devez être connecté pour consulter votre panier'], 401);
        }

        // Récupération de l'utilisateur connecté
        $user = Auth::user();

        // Récupération du panier de l'utilisateur avec les produits associés
        $paniers = $user->paniers()->with('produits')->first();

        // Vérification si le panier existe et contient des produits
        if (!$paniers || $paniers->produits->isEmpty()) {
            return response()->json(['message' => 'Votre panier est vide'], 200);
        }

        // Préparer la réponse avec les détails des produits dans le panier
        $produitsDansPanier = $paniers->produits->map(function ($produit) {
            return [
                'produit_id' => $produit->pivot->produit_id,  // Correction ici pour utiliser produit_id
                'nom' => $produit->nom_produit,
                'image_url' => $produit->image_url,
                'taille' => $produit->pivot->taille,
                'couleur' => $produit->pivot->couleur,
                'quantite' => $produit->pivot->quantite,
                'prix_total' => $produit->pivot->prix_total,
            ];
        });

        // Retourner les détails du panier
        return response()->json(['panier' => $produitsDansPanier], 200);
    }

    // Méthode pour ajouter un produit au panier
    public function ajouteraupaniers(Request $request, $produitId)
    {
        // Vérification si l'utilisateur est connecté
        if (!Auth::check()) {
            return response()->json(['message' => 'Vous devez être connecté pour ajouter des produits au panier'], 401);
        }

        // Recherche du produit dans la base de données
        $produit = Produits::find($produitId);

        // Vérification si le produit existe
        if (!$produit) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }

           // Récupération de la taille, de la couleur et de la quantité depuis la requête
    $tailleNom = $request->input('taille');
    $couleurNom = $request->input('couleur');
    $quantite = $request->input('quantite');

    // Vérification de la disponibilité de la taille
    $taille = Tailles::where('nom', $tailleNom)->first();
    if (!$taille) {
        return response()->json(['message' => 'La taille spécifiée n\'est pas valide pour ce produit'], 400);
    }

    // Vérification de la disponibilité de la couleur
    $couleur = Couleurs::where('nom', $couleurNom)->first();
    if (!$couleur) {
        return response()->json(['message' => 'La couleur spécifiée n\'est pas valide pour ce produit'], 400);
    }

    // Vérification de la quantité
//$quantiteDisponible = $produit->quantite_disponible()->where('tailles_id', $taille->id)->where('couleurs_id', $couleur->id)->first();
  //  if (!$quantiteDisponible || $quantite <= 0 || $quantite > $quantiteDisponible->quantite) {
    //    return response()->json(['message' => 'La quantité spécifiée n\'est pas disponible pour ce produit'], 400);
    //}
    $quantiteDisponible = Quantitedisponible::where('produits_id', $produitId)
    ->where('tailles_id', $taille->id)
    ->where('couleurs_id', $couleur->id)
    ->first();

if (!$quantiteDisponible || $quantite <= 0 || $quantite > $quantiteDisponible->quantite) {
    return response()->json(['message' => 'La quantité spécifiée n\'est pas disponible pour ce produit'], 400);
}
    // Ajout du produit au panier de l'utilisateur
    $user = Auth::user();
    $paniers = $user->paniers()->firstOrCreate([]);


    $prixTotal = $produit->prix * $quantite;
    // Ajoute le produit au panier
    $paniers->produits()->attach($produitId, [
        'taille' => $taille->nom,
        'couleur' => $couleur->nom,
        'quantite' => $quantite,
        'prix_total' => $prixTotal
    ]);

    return response()->json(['message' => 'Produit ajouté au panier avec succès'], 200);
   }
   public function mettreAJourPanier(Request $request, $produitId)
   {
       // Vérification si l'utilisateur est connecté
       if (!Auth::check()) {
           return response()->json(['message' => 'Vous devez être connecté pour mettre à jour le panier'], 401);
       }

       // Récupération de l'utilisateur et de son panier
       $user = Auth::user();
       $paniers = $user->paniers()->first();

       if (!$paniers) {
           return response()->json(['message' => 'Panier non trouvé'], 404);
       }

       // Recherche du produit dans la base de données
       $produit = Produits::find($produitId);

       if (!$produit) {
           return response()->json(['message' => 'Produit non trouvé'], 404);
       }

       // Récupération des attributs taille, couleur, et quantité
       $tailleNom = $request->input('taille');
       $couleurNom = $request->input('couleur');
       $quantite = $request->input('quantite');

       // Vérification de la taille
       $taille = Tailles::where('nom', $tailleNom)->first();
       if (!$taille) {
           return response()->json(['message' => 'La taille spécifiée n\'est pas valide pour ce produit'], 400);
       }

       // Vérification de la couleur
       $couleur = Couleurs::where('nom', $couleurNom)->first();
       if (!$couleur) {
           return response()->json(['message' => 'La couleur spécifiée n\'est pas valide pour ce produit'], 400);
       }

       // Vérification de la quantité disponible
       $quantiteDisponible = Quantitedisponible::where('produits_id', $produitId)
           ->where('tailles_id', $taille->id)
           ->where('couleurs_id', $couleur->id)
           ->first();

       if (!$quantiteDisponible || $quantite <= 0 || $quantite > $quantiteDisponible->quantite) {
           return response()->json(['message' => 'La quantité spécifiée n\'est pas disponible pour ce produit'], 400);
       }

       // Calcul du prix total pour cette ligne de commande
       $prixTotal = $produit->prix * $quantite;

       // Mise à jour du produit dans le panier
       $paniers->produits()->updateExistingPivot($produitId, [
           'taille' => $taille->nom,
           'couleur' => $couleur->nom,
           'quantite' => $quantite,
           'prix_total' => $prixTotal
       ]);

       return response()->json(['message' => 'Produit mis à jour dans le panier avec succès'], 200);
   }

   // Méthode pour retirer un produit du panier
   public function retirerDuPanier($produitId)
   {
       if (!Auth::check()) {
           return response()->json(['message' => 'Vous devez être connecté pour retirer des produits du panier'], 401);
       }

       $user = Auth::user();
       $paniers = $user->paniers()->first();

       if (!$paniers) {
           return response()->json(['message' => 'Panier non trouvé'], 404);
       }

       $paniers->produits()->detach($produitId);

       return response()->json(['message' => 'Produit retiré du panier avec succès'], 200);
   }
};
