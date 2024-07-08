<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CommandesController extends Controller
{
    public function commandi(Request $request)
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

        // Récupérer les paniers de l'utilisateur
        $paniers = $user->paniers;

        $paniersFiltres = collect();

        // Parcourir les paniers pour les filtrer
        foreach ($paniers as $panier) {
            // Vérifier si le panier contient au moins un produit sélectionné
            $paniersProduitsIds = $panier->produits->pluck('id')->toArray();
            if ($panier->produits && count(array_intersect($produitsIds, $paniersProduitsIds)) > 0) {
                $paniersFiltres->push($panier);
            }
        }

        // Vérifier si des paniers filtrés ont été trouvés
        if ($paniersFiltres->isEmpty()) {
            return response()->json(['message' => 'Aucun panier contenant les produits sélectionnés n\'a été trouvé'], 404);
        }

        // Calculer le montant total en parcourant les paniers filtrés et en additionnant les prix totaux des produits
        $montantTotal = $paniersFiltres->sum(function ($panier) {
            return $panier->produits->sum('pivot.prix_total');
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
            $commande = Commandesss::create([
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
                    'currency' => 'dt', // Modifier en fonction de votre devise
                    'source' => $request->stripeToken,
                    'description' => 'Paiement de la commande',
                ]);

                $paiement = Paiementss::create([
                    'user_id' => $user->id,
                    'commande_id' => $commande->id,
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
            foreach ($paniersFiltres as $panier) {
                $commande->paniers()->attach($panier->id, [
                    'quantite' => $panier->pivot->quantite,
                    'taille' => $panier->pivot->taille,
                    'couleur' => $panier->pivot->couleur,
                    'prix_total' => $panier->pivot->prix_total,
                ]);
            }

            // Effacer les paniers du client après la commande
            foreach ($paniersFiltres as $panier) {
                $panier->produits()->detach();
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
                'commandesss_id' => $commande->id,
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


}
