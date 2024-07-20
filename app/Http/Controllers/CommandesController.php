<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\Livraisondetails;
use App\Models\Paiements;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CommandeNotifiee;

// Assurez-vous également d'importer votre notification CommandeNotifiée
use App\Models\Produits;
use App\Models\Paniers;
use App\Models\Tailles;
use App\Models\Couleurs;
use App\Models\Quantitedisponible;
use App\Models\Commandes;
use App\Models\User; // Assurez-vous que le chemin est correct et qu'il pointe vers votre modèle User

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Charge;
use Stripe\Stripe;

class CommandesController extends Controller
{

    public function commander(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Vous devez être connecté pour passer une commande'], 401);
        }

        $validator = \Validator::make($request->all(), [
            'adresse' => 'required|string|max:255',
            'ville' => 'required|string|max:255',
            'code_postal' => 'required|string|max:10',
            'telephone' => 'required|string|max:20',
            'description' => 'nullable|string',
            'methode_paiement' => 'required|string|in:apres_livraison,par_carte',
            'stripeToken' => 'required_if:methode_paiement,par_carte|string',
            'numero_carte' => 'required_if:methode_paiement,par_carte|string|max:20',
            'nom_detenteur_carte' => 'required_if:methode_paiement,par_carte|string|max:255',
            'mois_validite' => 'required_if:methode_paiement,par_carte|integer|min:1|max:12',
            'annee_validite' => 'required_if:methode_paiement,par_carte|integer|min:' . date('Y'),
            'code_secret' => 'required_if:methode_paiement,par_carte|string|max:4',
            'adresse_facturation' => 'required_if:methode_paiement,par_carte|string|max:255',
            'produits' => 'required|array',
            'produits.*.id' => 'required|integer|exists:produits,id',
            'produits.*.quantite' => 'required|integer|min:1',
            'produits.*.taille' => 'required|string',
            'produits.*.couleur' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $validatedData = $validator->validated();
        $panier = Paniers::where('user_id', $user->id)->first();

        if (!$panier) {
            return response()->json(['message' => 'Votre panier est vide'], 400);
        }

        $produitsPanier = $panier->produits()->get()->keyBy('produit_id');

        // Convertir les produits validés en collection
        $produitsCommandeCollection = collect($validatedData['produits']);

        // Mise à jour du panier avec les produits spécifiés
        foreach ($validatedData['produits'] as $produitCommande) {
            $produit = Produits::find($produitCommande['id']);
            if (!$produit) {
                return response()->json(['message' => "Le produit avec l'ID {$produitCommande['id']} n'existe pas"], 400);
            }

            $taille = Tailles::where('nom', $produitCommande['taille'])->first();
            if (!$taille) {
                return response()->json(['message' => 'La taille spécifiée n\'est pas valide pour ce produit'], 400);
            }

            $couleur = Couleurs::where('nom', $produitCommande['couleur'])->first();
            if (!$couleur) {
                return response()->json(['message' => 'La couleur spécifiée n\'est pas valide pour ce produit'], 400);
            }

            $quantiteDisponible = Quantitedisponible::where('produits_id', $produitCommande['id'])
                ->where('tailles_id', $taille->id)
                ->where('couleurs_id', $couleur->id)
                ->first();

            if (!$quantiteDisponible || $produitCommande['quantite'] <= 0 || $produitCommande['quantite'] > $quantiteDisponible->quantite) {
                return response()->json(['message' => 'La quantité spécifiée n\'est pas disponible pour ce produit'], 400);
            }

            $prixTotal = $produit->prix * $produitCommande['quantite'];

            // Vérifiez si le produit existe déjà dans le panier
            if (isset($produitsPanier[$produitCommande['id']])) {
                $produitPanier = $produitsPanier[$produitCommande['id']];
                $nouvelleQuantite = $produitPanier->pivot->quantite + $produitCommande['quantite'];

                if ($nouvelleQuantite > $quantiteDisponible->quantite) {
                    return response()->json(['message' => 'La quantité totale demandée dépasse la quantité disponible en stock'], 400);
                }

                $panier->produits()->updateExistingPivot($produitCommande['id'], [
                    'quantite' => $nouvelleQuantite,
                    'taille' => $taille->nom,
                    'couleur' => $couleur->nom,
                    'prix_total' => $prixTotal
                ]);
            } else {
                $panier->produits()->attach($produitCommande['id'], [
                    'taille' => $taille->nom,
                    'couleur' => $couleur->nom,
                    'quantite' => $produitCommande['quantite'],
                    'prix_total' => $prixTotal
                ]);
            }
        }

        // Calcul du montant total
        $montantTotal = $panier->produits->sum(function ($produit) {
            return $produit->pivot->prix_total;
        });

        DB::beginTransaction();

        try {
            $commande = Commandes::create([
                'user_id' => $user->id,
                'montant_total' => $montantTotal,
                'statut' => 'en attente',
                'paiement_id' => null,
                'methode_paiement' => $request->methode_paiement,
            ]);

            $livraisonDetails = LivraisonDetails::create([
                'user_id' => $user->id,
                'commandes_id' => $commande->id,
                'adresse' => $request->adresse,
                'ville' => $request->ville,
                'code_postal' => $request->code_postal,
                'telephone' => $request->telephone,
                'description' => $request->description,
            ]);

            if ($request->methode_paiement === 'par_carte') {
                Stripe::setApiKey(env('STRIPE_SECRET'));

                $charge = Charge::create([
                    'amount' => $montantTotal * 100,
                    'currency' => 'eur',
                    'source' => $request->stripeToken,
                    'description' => 'Paiement de la commande',
                ]);

                $paiement = Paiements::create([
                    'user_id' => $user->id,
                    'commandes_id' => $commande->id,
                    'livraisondetails_id' => $livraisonDetails->id,
                    'methode_paiement' => $request->methode_paiement,
                    'numero_carte' => $request->numero_carte,
                    'nom_detenteur_carte' => $request->nom_detenteur_carte,
                    'mois_validite' => $request->mois_validite,
                    'annee_validite' => $request->annee_validite,
                    'code_secret' => $request->code_secret,
                    'adresse_facturation' => $request->adresse_facturation,
                    'prix_total' => $montantTotal,
                ]);

                $commande->update(['paiement_id' => $paiement->id]);
            }

            // Attacher les produits à la commande
            foreach ($validatedData['produits'] as $produitCommande) {
                $produit = Produits::find($produitCommande['id']);
                if (!$produit) {
                    return response()->json(['message' => "Le produit avec l'ID {$produitCommande['id']} n'existe pas"], 400);
                }

                $commande->produits()->attach($produitCommande['id'], [
                    'quantite' => $produitCommande['quantite'],
                    'taille' => $produitCommande['taille'],
                    'couleur' => $produitCommande['couleur'],
                    'prix_total' => $produit->prix * $produitCommande['quantite'],
                ]);

                // Mise à jour du stock
                $quantiteDisponible = Quantitedisponible::where('produits_id', $produitCommande['id'])
                    ->where('tailles_id', $taille->id)
                    ->where('couleurs_id', $couleur->id)
                    ->first();

                if ($quantiteDisponible) {
                    $quantiteDisponible->decrement('quantite', $produitCommande['quantite']);
                }
            }

            // Mise à jour du panier: retirer ou diminuer les quantités des produits commandés
            $idsProduitsCommandes = $produitsCommandeCollection->pluck('id')->toArray();
            foreach ($panier->produits as $produitDansPanier) {
                if (!in_array($produitDansPanier->id, $idsProduitsCommandes)) {
                    continue;
                }

                $produitCommande = $produitsCommandeCollection->firstWhere('id', $produitDansPanier->id);
                $quantiteRestante = $produitDansPanier->pivot->quantite - $produitCommande['quantite'];

                if ($quantiteRestante > 0) {
                    $panier->produits()->updateExistingPivot($produitDansPanier->id, [
                        'quantite' => $quantiteRestante,
                    ]);
                } else {
                    $panier->produits()->detach($produitDansPanier->id);
                }
            }

            DB::commit();

            // Envoyer une notification à l'administrateur
            $admins = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->get();

            Notification::send($admins, new CommandeNotifiee($commande));

            return response()->json(['message' => 'Commande passée avec succès.', 'commande' => $commande], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Une erreur est survenue lors du traitement de la commande.', 'error' => $e->getMessage()], 500);
        }
    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////
    public function passerlescommandes(Request $request)
{
    // Vérifier si l'utilisateur est connecté
    $user = Auth::user();
    if (!$user) {
        return response()->json(['message' => 'Vous devez être connecté pour passer une commande'], 401);
    }

    // Valider les entrées
    $validatedData = $request->validate([
        'produit_id' => 'required|array',
        'produit_id.*' => 'exists:produits,id',
        'adresse' => 'required|string|max:255',
        'ville' => 'required|string|max:255',
        'code_postal' => 'required|string|max:10',
        'telephone' => 'required|string|max:20',
        'description' => 'nullable|string',
        'methode_paiement' => 'required|string|in:apres_livraison,par_carte',
        'stripeToken' => 'required_if:methode_paiement,par_carte|string',
        'numero_carte' => 'required_if:methode_paiement,par_carte|string|max:20',
        'nom_detenteur_carte' => 'required_if:methode_paiement,par_carte|string|max:255',
        'mois_validite' => 'required_if:methode_paiement,par_carte|integer|min:1|max:12',
        'annee_validite' => 'required_if:methode_paiement,par_carte|integer|min:' . date('Y'),
        'code_secret' => 'required_if:methode_paiement,par_carte|string|max:4',
        'adresse_facturation' => 'required_if:methode_paiement,par_carte|string|max:255',
    ]);

    // Récupérer les IDs des produits sélectionnés à partir de la requête
    $produitsIds = $request->input('produit_id');

    // Récupérer les panier de l'utilisateur
    $paniers = $user->paniers;

    $paniersFiltres = collect();

    // Parcourir les panier pour les filtrer
    foreach ($paniers as $paniers) {
        // Vérifier si le panier contient au moins un produit sélectionné
        $paniersProduitsIds = $paniers->produits->pluck('id')->toArray();
        if ($paniers->produits && count(array_intersect($produitsIds, $paniersProduitsIds)) > 0) {
            $paniersFiltres->push($paniers);
        }
    }

    // Vérifier si des panier filtrés ont été trouvés
    if ($paniersFiltres->isEmpty()) {
        return response()->json(['message' => 'Aucun panier contenant les produits sélectionnés n\'a été trouvé'], 404);
    }

    // Calculer le montant total en parcourant les panier filtrés et en additionnant les prix totaux des produits
    $montantTotal = $paniersFiltres->sum(function ($paniers) {
        return $paniers->produits->sum('pivot.prix_total');
    });

    // Démarrer une transaction
    DB::beginTransaction();

    try {
        // Gestion des détails de livraison
        $livraisonDetails = Livraisondetails::updateOrCreate(
            ['user_id' => $user->id],
            [
                'adresse' => $request->adresse,
                'ville' => $request->ville,
                'code_postal' => $request->code_postal,
                'telephone' => $request->telephone,
                'description' => $request->description,
            ]
        );

        // Créer la commande
        $commande = Commandes::create([
            'user_id' => $user->id,
            'montant_total' => $montantTotal,
            'statut' => 'en attente',
            'paiement_id' => null,
            'methode_paiement' => $request->methode_paiement,
        ]);

        // Gestion des paiements
        if ($request->methode_paiement === 'par_carte') {
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $charge = Charge::create([
                'amount' => $montantTotal * 100, // Le montant doit être en cents
                'currency' => 'DT', // Modifier en fonction de votre devise
                'source' => $request->stripeToken,
                'description' => 'Paiement de la commande',
            ]);

            $paiement = Paiementss::create([
                'user_id' => $user->id,
                'commandes_id' => $commande->id,
                'livraisondetails_id' => $livraisonDetails->id,
                'methode_paiement' => $request->methode_paiement,
                'numero_carte' => $request->numero_carte,
                'nom_detenteur_carte' => $request->nom_detenteur_carte,
                'mois_validite' => $request->mois_validite,
                'annee_validite' => $request->annee_validite,
                'code_secret' => $request->code_secret,
                'adresse_facturation' => $request->adresse_facturation,
                'prix_total' => $montantTotal,
            ]);

            // Mettre à jour la commande avec l'ID du paiement
            $commande->update([
                'paiement_id' => $paiement->id,
            ]);
        }

        // Associer les paniers à la commande en utilisant la table pivot
        foreach ($paniersFiltres as $paniers) {
            $commande->paniers()->attach($paniers->id, [
                'quantite' => $paniers->pivot->quantite,
                'taille' => $paniers->pivot->taille,
                'couleur' => $paniers->pivot->couleur,
                'prix_total' => $paniers->pivot->prix_total,
            ]);
        }

        // Effacer les paniers du client après la commande
       foreach ($paniersFiltres as $paniers) {
$paniers->produits()->detach();
       }


        // Valider la transaction
        DB::commit();

        // Retourner une réponse JSON pour confirmer la commande
        return response()->json(['message' => 'Commande passée avec succès.', 'commande' => $commande], 201);

    } catch (\Exception $e) {
        // En cas d'erreur, annuler la transaction
        DB::rollBack();

        // Gérer l'erreur
        return response()->json(['message' => 'Une erreur est survenue lors du traitement de la commande.', 'error' => $e->getMessage()], 500);
    }
}

public function passerCommande(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Vous devez être connecté pour passer une commande'], 401);
        }

        $validator = \Validator::make($request->all(), [
            'adresse' => 'required|string|max:255',
            'ville' => 'required|string|max:255',
            'code_postal' => 'required|string|max:10',
            'telephone' => 'required|string|max:20',
            'description' => 'nullable|string',
            'methode_paiement' => 'required|string|in:apres_livraison,par_carte',
            'stripeToken' => 'required_if:methode_paiement,par_carte|string',
            'numero_carte' => 'required_if:methode_paiement,par_carte|string|max:20',
            'nom_detenteur_carte' => 'required_if:methode_paiement,par_carte|string|max:255',
            'mois_validite' => 'required_if:methode_paiement,par_carte|integer|min:1|max:12',
            'annee_validite' => 'required_if:methode_paiement,par_carte|integer|min:' . date('Y'),
            'code_secret' => 'required_if:methode_paiement,par_carte|string|max:4',
            'adresse_facturation' => 'required_if:methode_paiement,par_carte|string|max:255',
            'produits' => 'required|array',
            'produits.*.id' => 'required|integer|exists:produits,id',
            'produits.*.quantite' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $validatedData = $validator->validated();

        $panier = Paniers::where('user_id', $user->id)->first();

        if (!$panier || $panier->produits->isEmpty()) {
            return response()->json(['message' => 'Votre panier est vide'], 400);
        }

        $montantTotal = 0;
        $produitsCommandes = [];

        foreach ($validatedData['produits'] as $produitCommande) {
            $produitDansPanier = $panier->produits->find($produitCommande['id']);
            if (!$produitDansPanier) {
                return response()->json(['message' => "Le produit avec l'ID {$produitCommande['id']} n'existe pas dans le panier"], 400);
            }
            $montantTotal += $produitDansPanier->pivot->prix_total * $produitCommande['quantite'];
            $produitsCommandes[] = [
                'produit' => $produitDansPanier,
                'quantite' => $produitCommande['quantite']
            ];
        }

        DB::beginTransaction();

        try {
            $commande = Commandes::create([
                'user_id' => $user->id,
                'montant_total' => $montantTotal,
                'statut' => 'en attente',
                'paiement_id' => null,
                'methode_paiement' => $request->methode_paiement,
            ]);

            $livraisonDetails = LivraisonDetails::create([
                'user_id' => $user->id,
                'commandes_id' => $commande->id,
                'adresse' => $request->adresse,
                'ville' => $request->ville,
                'code_postal' => $request->code_postal,
                'telephone' => $request->telephone,
                'description' => $request->description,
            ]);

            if ($request->methode_paiement === 'par_carte') {
                Stripe::setApiKey(env('STRIPE_SECRET'));

                $charge = Charge::create([
                    'amount' => $montantTotal * 100,
                    'currency' => 'eur',
                    'source' => $request->stripeToken,
                    'description' => 'Paiement de la commande',
                ]);

                $paiement = Paiements::create([
                    'user_id' => $user->id,
                    'commandes_id' => $commande->id,
                    'livraisondetails_id' => $livraisonDetails->id,
                    'methode_paiement' => $request->methode_paiement,
                    'numero_carte' => $request->numero_carte,
                    'nom_detenteur_carte' => $request->nom_detenteur_carte,
                    'mois_validite' => $request->mois_validite,
                    'annee_validite' => $request->annee_validite,
                    'code_secret' => $request->code_secret,
                    'adresse_facturation' => $request->adresse_facturation,
                    'prix_total' => $montantTotal,
                ]);

                $commande->update(['paiement_id' => $paiement->id]);
            }

            foreach ($produitsCommandes as $produitCommande) {
                $commande->produits()->attach($produitCommande['produit']->id, [
                    'quantite' => $produitCommande['quantite'],
                    'taille' => $produitCommande['produit']->pivot->taille,
                    'couleur' => $produitCommande['produit']->pivot->couleur,
                    'prix_total' => $produitCommande['produit']->pivot->prix_total * $produitCommande['quantite'],
                ]);

                // Mise à jour ou détachement du produit commandé du panier
                $nouvelleQuantite = $produitCommande['produit']->pivot->quantite - $produitCommande['quantite'];
                if ($nouvelleQuantite > 0) {
                    $panier->produits()->updateExistingPivot($produitCommande['produit']->id, ['quantite' => $nouvelleQuantite]);
                } else {
                    $panier->produits()->detach($produitCommande['produit']->id);
                }
            }

            DB::commit();

            // Envoyer une notification à l'administrateur
            $admins = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->get();

            Notification::send($admins, new CommandeNotifiee($commande));

            return response()->json(['message' => 'Commande passée avec succès.', 'commande' => $commande], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Une erreur est survenue lors du traitement de la commande.', 'error' => $e->getMessage()], 500);
        }
    }
}
