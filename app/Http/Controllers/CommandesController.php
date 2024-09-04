<?php

namespace App\Http\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use Illuminate\Support\Facades\DB;
use App\Models\Livraisondetails;
use App\Models\Paiements;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CommandeNotifiee;
use Illuminate\Support\Facades\Log;
use App\Exports\CommandesExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Models\configuration;


use App\Models\Produits;
use App\Models\Paniers;
use App\Models\Tailles;
use App\Models\Couleurs;
use App\Models\Quantitedisponible;
use App\Models\Commandes;
use App\Models\User;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Charge;
use Stripe\Stripe;
use Stripe\Refund;
class CommandesController extends Controller
{


    public function updateFraisLivraison(Request $request)
    {     $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        // Vérifier que l'utilisateur est authentifié et qu'il a le rôle d'administrateur


        // Valider la requête
        $request->validate([
            'frais_livraison' => 'required|numeric|min:0',
        ]);

        // Mettre à jour ou créer la configuration pour les frais de livraison
        $config = Configuration::updateOrCreate(
            ['key' => 'frais_livraison'],
            ['value' => $request->input('frais_livraison')]
        );

        return response()->json(['message' => 'Frais de livraison mis à jour avec succès.'], 200);
    }
    public function obtenirFraisLivraison()
{
    $config = \App\Models\Configuration::where('key', 'frais_livraison')->first();
    return $config ? $config->value : 0.00; // Retourne 0 si la configuration n'existe pas
}

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

        $produitsCommandeCollection = collect($validatedData['produits']);

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

        $montantTotalProduits = $panier->produits->sum(function ($produit) {
            return $produit->pivot->prix_total;
        });

        $fraisLivraison = $this->obtenirFraisLivraison();

        $montantTotal = $montantTotalProduits + $fraisLivraison;


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

                $quantiteDisponible = Quantitedisponible::where('produits_id', $produitCommande['id'])
                    ->where('tailles_id', $taille->id)
                    ->where('couleurs_id', $couleur->id)
                    ->first();

                if ($quantiteDisponible) {
                    $quantiteDisponible->decrement('quantite', $produitCommande['quantite']);
                }
            }

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

    public function detailsCommandes()
    {
        $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }


        // Récupérer toutes les commandes avec les détails associés
        $commandes = Commandes::with(['livraisonDetails', 'produits' => function ($query) {
            $query->select('produits.id', 'produits.references', 'produits.nom_produit');
        }, 'paiement'])->get();

        $result = $commandes->map(function ($commande) {
            return [
                'commande_id' => $commande->id,
                'montant_total' => $commande->montant_total,
                'statut' => $commande->statut,
                'frais_livraison' => $this->obtenirFraisLivraison(),
                'details_livraison' => [
                    'adresse' => $commande->livraisonDetails->adresse,
                    'ville' => $commande->livraisonDetails->ville,
                    'code_postal' => $commande->livraisonDetails->code_postal,
                    'telephone' => $commande->livraisonDetails->telephone,
                    'description' => $commande->livraisonDetails->description,
                ],
                'produits_commandes' => $commande->produits->map(function ($produit) {
                    return [
                        'produit_id' => $produit->id,
                        'reference' => $produit->references,
                        'nom' => $produit->nom_produit,
                        'quantite' => $produit->pivot->quantite,
                        'taille' => $produit->pivot->taille,
                        'couleur' => $produit->pivot->couleur,
                        'prix_total' => $produit->pivot->prix_total,
                    ];
                }),
                'details_paiement' => $commande->paiement ? [
                    'methode_paiement' => $commande->paiement->methode_paiement,
                    'adresse_facturation' => $commande->paiement->adresse_facturation,
                    'prix_total' => $commande->paiement->prix_total,
                ] : null,
            ];
        });

        return response()->json(['commandes' => $result], 200);
    }
    public function exporterCommandePDF($commande_id)
    {
        // Vérifier que l'utilisateur est authentifié et qu'il a le rôle d'administrateur
        $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }


        // Récupérer la commande avec les détails associés
        $commande = Commandes::with(['livraisonDetails', 'produits', 'paiement'])->find($commande_id);

        if (!$commande) {
            return response()->json(['message' => 'Commande non trouvée'], 404);
        }

        // Préparer les données pour la vue PDF
        $data = [
            'commande_id' => $commande->id,
            'montant_total' => $commande->montant_total,
            'statut' => $commande->statut,
            'frais_livraison' => $this->obtenirFraisLivraison(),
            'details_livraison' => $commande->livraisonDetails,
            'produits_commandes' => $commande->produits->map(function($produit) {
                return [
                    'id' => $produit->id,
                    'reference' => $produit->references,
                    'nom' => $produit->nom_produit,
                    'quantite' => $produit->pivot->quantite,
                    'taille' => $produit->pivot->taille,
                    'couleur' => $produit->pivot->couleur,
                    'prix_total' => $produit->pivot->prix_total,
                ];
            }),
            'details_paiement' => $commande->paiement ? [
                'methode_paiement' => $commande->paiement->methode_paiement,
                'adresse_facturation' => $commande->paiement->adresse_facturation,
                'prix_total' => $commande->paiement->prix_total,
            ] : null,
        ];

        // Charger la vue pour générer le PDF
        $pdf = PDF::loadView('commandes.details_pdf', $data);

        // Téléchargement du fichier PDF
        return $pdf->download('commande_'.$commande_id.'.pdf');
    }

    public function exporterCommandes()
    {
        // Vérifier que l'utilisateur est authentifié et qu'il a le rôle d'administrateur
        $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }


        // Récupérer toutes les commandes avec les détails associés
        $commandes = Commandes::with(['livraisonDetails', 'produits', 'paiement'])->get();

        // Créer une nouvelle instance de Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Ajouter les en-têtes des colonnes
        $sheet->setCellValue('A1', 'Commande ID');
        $sheet->setCellValue('B1', 'Produit');
        $sheet->setCellValue('C1', 'Quantité');
        $sheet->setCellValue('D1', 'Prix');
        $sheet->setCellValue('E1', 'Date de Livraison');
        $sheet->setCellValue('F1', 'Méthode de Paiement');
        $sheet->setCellValue('G1', 'Adresse');
        $sheet->setCellValue('H1', 'Ville');
        $sheet->setCellValue('I1', 'Code Postal');
        $sheet->setCellValue('J1', 'Téléphone');
        // Ajoutez d'autres colonnes en fonction de vos besoins

        // Remplir les données
        $row = 2; // La ligne où commencer à écrire les données
        foreach ($commandes as $commande) {
            foreach ($commande->produits as $produit) {
                $sheet->setCellValue('A' . $row, $commande->id);
                $sheet->setCellValue('B' . $row, $produit->nom_produit);
                $sheet->setCellValue('C' . $row, $produit->pivot->quantite);
                $sheet->setCellValue('D' . $row, $produit->pivot->prix_total);
                $sheet->setCellValue('E' . $row, $commande->livraisonDetails->adresse);
                $sheet->setCellValue('F' . $row, $commande->methode_paiement);
                $sheet->setCellValue('G' . $row, $commande->livraisonDetails->adresse);
                $sheet->setCellValue('H' . $row, $commande->livraisonDetails->ville);
                $sheet->setCellValue('I' . $row, $commande->livraisonDetails->code_postal);
                $sheet->setCellValue('J' . $row, $commande->livraisonDetails->telephone);
                $row++;
            }
        }

        // Créer un objet Writer et sauvegarder le fichier Excel
        $writer = new Xlsx($spreadsheet);
        $filename = 'commandes.xlsx';

        // Répondre avec le fichier Excel pour téléchargement
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }


    public function changerStatutCommande(Request $request, $commandeId)
    {
        $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $commande = Commandes::find($commandeId);

        if (!$commande) {
            return response()->json(['message' => 'Commande non trouvée.'], 404);
        }

        $nouveauStatut = $request->input('statut');

        // Si le statut est "annuler", annuler la transaction associée
        if ($nouveauStatut === 'annuler' && $commande->paiement_id) {
            try {
                Stripe::setApiKey(env('STRIPE_SECRET'));

                // Récupérer les détails de paiement
                $paiement = Paiements::find($commande->paiement_id);

                if ($paiement) {
                    // Annuler la transaction via Stripe
                    Refund::create([
                        'charge' => $paiement->stripe_charge_id,
                    ]);
                }
            } catch (\Exception $e) {
                return response()->json(['message' => 'Échec de l\'annulation de la transaction.', 'error' => $e->getMessage()], 500);
            }
        }

        // Mettre à jour le statut de la commande
        $commande->update(['statut' => $nouveauStatut]);

        return response()->json(['message' => 'Le statut de la commande a été mis à jour avec succès.', 'commande' => $commande], 200);
    }

    public function voirDetailsCommande()
    {
        $user = Auth::user();

        // Vérifiez que l'utilisateur est authentifié
        if (!$user) {
            return response()->json(['message' => 'Vous devez être connecté pour voir les détails de votre commande'], 401);
        }

        // Récupérer l'ID de la commande associée à l'utilisateur connecté
        $commandeId = Commandes::where('user_id', $user->id)->pluck('id')->first();

        if (!$commandeId) {
            return response()->json(['message' => 'Aucune commande trouvée pour cet utilisateur'], 404);
        }

        // Récupérer la commande en fonction de l'ID et charger les relations nécessaires
        $commande = Commandes::where('id', $commandeId)
            ->with(['produits', 'livraisondetails', 'paiement']) // Charger les relations nécessaires
            ->first();

        return response()->json(['commande' => $commande], 200);
    }



    public function voirDetailsCommandepouradmin($commandeId)
    {
        $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Récupérer la commande spécifique avec les détails associés
        $commande = Commandes::where('id', $commandeId)
            ->with(['livraisonDetails', 'produits' => function ($query) {
                $query->select('produits.id', 'produits.references', 'produits.nom_produit');
            }, 'paiement'])
            ->first();

        if (!$commande) {
            return response()->json(['message' => 'Commande non trouvée'], 404);
        }

        $result = [
            'commande_id' => $commande->id,
            'montant_total' => $commande->montant_total,
            'statut' => $commande->statut,
            'frais_livraison' => $this->obtenirFraisLivraison(),
            'details_livraison' => $commande->livraisonDetails ? [
                'adresse' => $commande->livraisonDetails->adresse,
                'ville' => $commande->livraisonDetails->ville,
                'code_postal' => $commande->livraisonDetails->code_postal,
                'telephone' => $commande->livraisonDetails->telephone,
                'description' => $commande->livraisonDetails->description,
            ] : null,
            'produits_commandes' => $commande->produits->map(function ($produit) {
                return [
                    'produit_id' => $produit->id,
                    'reference' => $produit->references,
                    'nom' => $produit->nom_produit,
                    'quantite' => $produit->pivot->quantite,
                    'taille' => $produit->pivot->taille,
                    'couleur' => $produit->pivot->couleur,
                    'prix_total' => $produit->pivot->prix_total,
                ];
            }),
            'details_paiement' => $commande->paiement ? [
                'methode_paiement' => $commande->paiement->methode_paiement,
                'adresse_facturation' => $commande->paiement->adresse_facturation,
                'prix_total' => $commande->paiement->prix_total,
            ] : null,
        ];

        return response()->json(['commande' => $result], 200);
    }

}








    /////////////////////////////////////////////////////////////////////////////////////////////////////
//     public function passerlescommandes(Request $request)
// {
//     // Vérifier si l'utilisateur est connecté
//     $user = Auth::user();
//     if (!$user) {
//         return response()->json(['message' => 'Vous devez être connecté pour passer une commande'], 401);
//     }

//     // Valider les entrées
//     $validatedData = $request->validate([
//         'produit_id' => 'required|array',
//         'produit_id.*' => 'exists:produits,id',
//         'adresse' => 'required|string|max:255',
//         'ville' => 'required|string|max:255',
//         'code_postal' => 'required|string|max:10',
//         'telephone' => 'required|string|max:20',
//         'description' => 'nullable|string',
//         'methode_paiement' => 'required|string|in:apres_livraison,par_carte',
//         'stripeToken' => 'required_if:methode_paiement,par_carte|string',
//         'numero_carte' => 'required_if:methode_paiement,par_carte|string|max:20',
//         'nom_detenteur_carte' => 'required_if:methode_paiement,par_carte|string|max:255',
//         'mois_validite' => 'required_if:methode_paiement,par_carte|integer|min:1|max:12',
//         'annee_validite' => 'required_if:methode_paiement,par_carte|integer|min:' . date('Y'),
//         'code_secret' => 'required_if:methode_paiement,par_carte|string|max:4',
//         'adresse_facturation' => 'required_if:methode_paiement,par_carte|string|max:255',
//     ]);

//     // Récupérer les IDs des produits sélectionnés à partir de la requête
//     $produitsIds = $request->input('produit_id');

//     // Récupérer les panier de l'utilisateur
//     $paniers = $user->paniers;

//     $paniersFiltres = collect();

//     // Parcourir les panier pour les filtrer
//     foreach ($paniers as $paniers) {
//         // Vérifier si le panier contient au moins un produit sélectionné
//         $paniersProduitsIds = $paniers->produits->pluck('id')->toArray();
//         if ($paniers->produits && count(array_intersect($produitsIds, $paniersProduitsIds)) > 0) {
//             $paniersFiltres->push($paniers);
//         }
//     }

//     // Vérifier si des panier filtrés ont été trouvés
//     if ($paniersFiltres->isEmpty()) {
//         return response()->json(['message' => 'Aucun panier contenant les produits sélectionnés n\'a été trouvé'], 404);
//     }

//     // Calculer le montant total en parcourant les panier filtrés et en additionnant les prix totaux des produits
//     $montantTotal = $paniersFiltres->sum(function ($paniers) {
//         return $paniers->produits->sum('pivot.prix_total');
//     });

//     // Démarrer une transaction
//     DB::beginTransaction();

//     try {
//         // Gestion des détails de livraison
//         $livraisonDetails = Livraisondetails::updateOrCreate(
//             ['user_id' => $user->id],
//             [
//                 'adresse' => $request->adresse,
//                 'ville' => $request->ville,
//                 'code_postal' => $request->code_postal,
//                 'telephone' => $request->telephone,
//                 'description' => $request->description,
//             ]
//         );

//         // Créer la commande
//         $commande = Commandes::create([
//             'user_id' => $user->id,
//             'montant_total' => $montantTotal,
//             'statut' => 'en attente',
//             'paiement_id' => null,
//             'methode_paiement' => $request->methode_paiement,
//         ]);

//         // Gestion des paiements
//         if ($request->methode_paiement === 'par_carte') {
//             Stripe::setApiKey(env('STRIPE_SECRET'));

//             $charge = Charge::create([
//                 'amount' => $montantTotal * 100, // Le montant doit être en cents
//                 'currency' => 'DT', // Modifier en fonction de votre devise
//                 'source' => $request->stripeToken,
//                 'description' => 'Paiement de la commande',
//             ]);

//             $paiement = Paiementss::create([
//                 'user_id' => $user->id,
//                 'commandes_id' => $commande->id,
//                 'livraisondetails_id' => $livraisonDetails->id,
//                 'methode_paiement' => $request->methode_paiement,
//                 'numero_carte' => $request->numero_carte,
//                 'nom_detenteur_carte' => $request->nom_detenteur_carte,
//                 'mois_validite' => $request->mois_validite,
//                 'annee_validite' => $request->annee_validite,
//                 'code_secret' => $request->code_secret,
//                 'adresse_facturation' => $request->adresse_facturation,
//                 'prix_total' => $montantTotal,
//             ]);

//             // Mettre à jour la commande avec l'ID du paiement
//             $commande->update([
//                 'paiement_id' => $paiement->id,
//             ]);
//         }

//         // Associer les paniers à la commande en utilisant la table pivot
//         foreach ($paniersFiltres as $paniers) {
//             $commande->paniers()->attach($paniers->id, [
//                 'quantite' => $paniers->pivot->quantite,
//                 'taille' => $paniers->pivot->taille,
//                 'couleur' => $paniers->pivot->couleur,
//                 'prix_total' => $paniers->pivot->prix_total,
//             ]);
//         }

//         // Effacer les paniers du client après la commande
//        foreach ($paniersFiltres as $paniers) {
// $paniers->produits()->detach();
//        }


//         // Valider la transaction
//         DB::commit();

//         // Retourner une réponse JSON pour confirmer la commande
//         return response()->json(['message' => 'Commande passée avec succès.', 'commande' => $commande], 201);

//     } catch (\Exception $e) {
//         // En cas d'erreur, annuler la transaction
//         DB::rollBack();

//         // Gérer l'erreur
//         return response()->json(['message' => 'Une erreur est survenue lors du traitement de la commande.', 'error' => $e->getMessage()], 500);
//     }
// }

// public function passerCommande(Request $request)
//     {
//         $user = Auth::user();
//         if (!$user) {
//             return response()->json(['message' => 'Vous devez être connecté pour passer une commande'], 401);
//         }

//         $validator = \Validator::make($request->all(), [
//             'adresse' => 'required|string|max:255',
//             'ville' => 'required|string|max:255',
//             'code_postal' => 'required|string|max:10',
//             'telephone' => 'required|string|max:20',
//             'description' => 'nullable|string',
//             'methode_paiement' => 'required|string|in:apres_livraison,par_carte',
//             'stripeToken' => 'required_if:methode_paiement,par_carte|string',
//             'numero_carte' => 'required_if:methode_paiement,par_carte|string|max:20',
//             'nom_detenteur_carte' => 'required_if:methode_paiement,par_carte|string|max:255',
//             'mois_validite' => 'required_if:methode_paiement,par_carte|integer|min:1|max:12',
//             'annee_validite' => 'required_if:methode_paiement,par_carte|integer|min:' . date('Y'),
//             'code_secret' => 'required_if:methode_paiement,par_carte|string|max:4',
//             'adresse_facturation' => 'required_if:methode_paiement,par_carte|string|max:255',
//             'produits' => 'required|array',
//             'produits.*.id' => 'required|integer|exists:produits,id',
//             'produits.*.quantite' => 'required|integer|min:1',
//         ]);

//         if ($validator->fails()) {
//             return response()->json(['errors' => $validator->errors()], 400);
//         }

//         $validatedData = $validator->validated();

//         $panier = Paniers::where('user_id', $user->id)->first();

//         if (!$panier || $panier->produits->isEmpty()) {
//             return response()->json(['message' => 'Votre panier est vide'], 400);
//         }

//         $montantTotal = 0;
//         $produitsCommandes = [];

//         foreach ($validatedData['produits'] as $produitCommande) {
//             $produitDansPanier = $panier->produits->find($produitCommande['id']);
//             if (!$produitDansPanier) {
//                 return response()->json(['message' => "Le produit avec l'ID {$produitCommande['id']} n'existe pas dans le panier"], 400);
//             }
//             $montantTotal += $produitDansPanier->pivot->prix_total * $produitCommande['quantite'];
//             $produitsCommandes[] = [
//                 'produit' => $produitDansPanier,
//                 'quantite' => $produitCommande['quantite']
//             ];
//         }

//         DB::beginTransaction();

//         try {
//             $commande = Commandes::create([
//                 'user_id' => $user->id,
//                 'montant_total' => $montantTotal,
//                 'statut' => 'en attente',
//                 'paiement_id' => null,
//                 'methode_paiement' => $request->methode_paiement,
//             ]);

//             $livraisonDetails = LivraisonDetails::create([
//                 'user_id' => $user->id,
//                 'commandes_id' => $commande->id,
//                 'adresse' => $request->adresse,
//                 'ville' => $request->ville,
//                 'code_postal' => $request->code_postal,
//                 'telephone' => $request->telephone,
//                 'description' => $request->description,
//             ]);

//             if ($request->methode_paiement === 'par_carte') {
//                 Stripe::setApiKey(env('STRIPE_SECRET'));

//                 $charge = Charge::create([
//                     'amount' => $montantTotal * 100,
//                     'currency' => 'eur',
//                     'source' => $request->stripeToken,
//                     'description' => 'Paiement de la commande',
//                 ]);

//                 $paiement = Paiements::create([
//                     'user_id' => $user->id,
//                     'commandes_id' => $commande->id,
//                     'livraisondetails_id' => $livraisonDetails->id,
//                     'methode_paiement' => $request->methode_paiement,
//                     'numero_carte' => $request->numero_carte,
//                     'nom_detenteur_carte' => $request->nom_detenteur_carte,
//                     'mois_validite' => $request->mois_validite,
//                     'annee_validite' => $request->annee_validite,
//                     'code_secret' => $request->code_secret,
//                     'adresse_facturation' => $request->adresse_facturation,
//                     'prix_total' => $montantTotal,
//                 ]);

//                 $commande->update(['paiement_id' => $paiement->id]);
//             }

//             foreach ($produitsCommandes as $produitCommande) {
//                 $commande->produits()->attach($produitCommande['produit']->id, [
//                     'quantite' => $produitCommande['quantite'],
//                     'taille' => $produitCommande['produit']->pivot->taille,
//                     'couleur' => $produitCommande['produit']->pivot->couleur,
//                     'prix_total' => $produitCommande['produit']->pivot->prix_total * $produitCommande['quantite'],
//                 ]);

//                 // Mise à jour ou détachement du produit commandé du panier
//                 $nouvelleQuantite = $produitCommande['produit']->pivot->quantite - $produitCommande['quantite'];
//                 if ($nouvelleQuantite > 0) {
//                     $panier->produits()->updateExistingPivot($produitCommande['produit']->id, ['quantite' => $nouvelleQuantite]);
//                 } else {
//                     $panier->produits()->detach($produitCommande['produit']->id);
//                 }
//             }

//             DB::commit();

//             // Envoyer une notification à l'administrateur
//             $admins = User::whereHas('roles', function ($query) {
//                 $query->where('name', 'admin');
//             })->get();

//             Notification::send($admins, new CommandeNotifiee($commande));

//             return response()->json(['message' => 'Commande passée avec succès.', 'commande' => $commande], 201);
//         } catch (\Exception $e) {
//             DB::rollBack();
//             return response()->json(['message' => 'Une erreur est survenue lors du traitement de la commande.', 'error' => $e->getMessage()], 500);
//         }
//     }
// }
