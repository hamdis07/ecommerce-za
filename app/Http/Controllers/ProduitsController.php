<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Models\Produits;
use App\Models\Categories;
use App\Models\Genre;
use App\Models\SousCategories;
use App\Models\Tailles;
use App\Models\Couleurs;
use App\Models\Promos;
use App\Models\images;
use Illuminate\Support\Facades\Storage; // Importer la classe Storage

use App\Models\Quantitedisponible;
use App\Models\Magasins;

class ProduitsController extends Controller
{


 public function nouveauproduit(Request $request)
 {

     try {
         // Validation des données du formulaire pour le produit
         $request->validate([
             'references' => 'required|string',
             'nom_produit' => 'required|string',
             'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Validation des images
             'image_url' => 'nullable|file|mimetypes:image/jpeg,image/png,image/jpg,image/gif,video/mp4,video/mpeg,video/quicktime|max:204800', // Validation pour les images et les vidéos
             'description' => 'required|string',
             'prix_initial'=> 'required|numeric',
             'composition' => 'required|string',
             'entretien' => 'required|string',
             'genre' => 'required|string',
             'categorie' => 'required|string',
             'sous_categorie' => 'required|string',
             'mots_cles'=>'nullable|string',
             'magasins' => 'required|array|min:1',
             'magasins.*.nom' => 'required|string',
             'magasins.*.adresse' => 'required|string',
             'magasins.*.ville' => 'required|string',
             'magasins.*.code_postal' => 'required|string',
             'magasins.*.responsable' => 'required|string',
             'magasins.*.telephone' => 'required|string',
             'magasins.*.tailles' => 'required|array|min:1',
             'magasins.*.couleurs' => 'required|array|min:1',
             'magasins.*.quantites' => 'required|array|min:1',
         ]);
        // Logique pour traiter les images

         // Vérification et création du genre
         $genre = Genre::firstOrCreate(['nom' => $request->genre]);

         // Vérification et création de la catégorie
         $categorie = Categories::firstOrCreate(['nom' => $request->categorie]);

         // Vérification et création de la sous-catégorie avec son association à la catégorie
         $sousCategorie = Sous_Categories::firstOrCreate(['nom' => $request->sous_categorie, 'categorie_id' => $categorie->id]);




         // Création d'un nouveau produit
         $produit = new Produits();
         $produit->references = $request->references;
         $produit->nom_produit = $request->nom_produit;
         $produit->description = $request->description;
         $produit->composition = $request->composition;
         $produit->entretien = $request->entretien;
         $produit->prix_initial = $request->prix_initial;
         $produit->prix = $request->prix_initial; // Assigner la valeur de prix_initial à prix
         $produit->genre_id = $genre->id;
         $produit->categorie_id = $categorie->id;
         $produit->sous_categorie_id = $sousCategorie->id;
         $produit->mots_cles = $request->mots_cles;


         // Traitement des images


         // Traitement de la vidéo
         if ($request->hasFile('image_url')) {
             $video = $request->file('image_url');
             $videoName = $video->getClientOriginalName();
             $video->storeAs('public/videos', $videoName); // Stockage de la vidéo dans le dossier "storage/app/public/videos"
             $produit->image_url = Storage::url('videos/' . $videoName); // Assurez-vous d'ajuster cela selon votre modèle
         }

         $produit->save();
         $produit_id = $produit->getKey(); // ou $produit_id = $produit->id;

         if ($request->hasFile('images')) {
            $image = $request->file('images');
            foreach ($request->file('images') as $imageFile) {
                $imageName = $imageFile->getClientOriginalName();
                $imageFile->storeAs('public/images', $imageName); // Stockage de l'image dans le dossier "storage/app/public/images"

                // Création de l'enregistrement d'image dans la table images
                $image = new images();
                $image->chemin_image = 'public/images/' . $imageName; // Chemin d'accès relatif à l'image
                $image->produit_id = $produit->id; // ID du produit associé à cette image
                $image->save();
            }
        }


         // Pour chaque magasin
         foreach ($request->magasins as $magasinData) {
             // Vérifiez si le magasin existe déjà
             $magasin = Magasins::where('nom', $magasinData['nom'])
                                 ->where('adresse', $magasinData['adresse'])
                                 ->where('ville', $magasinData['ville'])
                                 ->where('code_postal', $magasinData['code_postal'])
                                 ->where('responsable', $magasinData['responsable'])
                                 ->where('telephone', $magasinData['telephone'])
                                 ->first();

             if (!$magasin) {
                 // Le magasin n'existe pas encore, alors créez-le
                 $magasin = Magasins::create([
                     'nom' => $magasinData['nom'],
                     'adresse' => $magasinData['adresse'],
                     'ville' => $magasinData['ville'],
                     'code_postal' => $magasinData['code_postal'],
                     'responsable' => $magasinData['responsable'],
                     'telephone' => $magasinData['telephone']
                 ]);
             }

             // Pour chaque couleur, taille et quantité associée à ce magasin
             foreach ($magasinData['couleurs'] as $index => $couleur) {
                 // Vérifiez et créez la couleur si elle n'existe pas déjà
                 $nouvelleCouleur = Couleurs::firstOrCreate(['nom' => $couleur]);

                 // Vérifiez et créez la taille si elle n'existe pas déjà
                 $nouvelleTaille = Tailles::firstOrCreate(['nom' => $magasinData['tailles'][$index]]);

                 // Créez une nouvelle quantité disponible pour ce produit, cette couleur, cette taille et ce magasin
                 $quantite = new Quantite_disponible();
                 $quantite->magasin_id = $magasin->id;
                 $quantite->produits_id = $produit->id;
                 $quantite->couleurs_id = $nouvelleCouleur->id;
                 $quantite->tailles_id = $nouvelleTaille->id;
                 $quantite->quantite = $magasinData['quantites'][$index];
                 $quantite->save();
             }
         }

         // Redirection vers une page de succès ou d'accueil
         return response()->json(['message' => 'Produit ajouté avec succès', 'produit' => $produit], 201);
     } catch (\Illuminate\Validation\ValidationException $e) {
         // Gérer l'erreur de validation
         return response()->json(['error' => $e->validator->errors()], 400);
     } catch (\Exception $e) {
         // Gérer toute autre exception
         return response()->json(['error' => $e->getMessage()], 500);
     }
 }

 public function modifierProduit(Request $request, $id)
 {
     try {
         // Validation des données du formulaire pour le produit
         $request->validate([
             'nom_produit' => 'sometimes|string',
             'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048', // Validation des images
             'image_url' => 'sometimes|file|mimetypes:image/jpeg,image/png,image/jpg,image/gif,video/mp4,video/mpeg,video/quicktime|max:204800', // Validation pour les images et les vidéos
             'description' => 'sometimes|string',
             'prix_initial'=> 'sometimes|numeric',
             'composition' => 'sometimes|string',
             'entretien' => 'sometimes|string',
             'genre' => 'sometimes|string',
             'categorie' => 'sometimes|string',
             'sous_categorie' => 'sometimes|string',
             'mots_cles'=>'sometimes|string',
             'magasins' => 'sometimes|array|min:1',
             'magasins.*.nom' => 'sometimes|string',
             'magasins.*.adresse' => 'sometimes|string',
             'magasins.*.ville' => 'sometimes|string',
             'magasins.*.code_postal' => 'sometimes|string',
             'magasins.*.responsable' => 'sometimes|string',
             'magasins.*.telephone' => 'sometimes|string',
             'magasins.*.tailles' => 'sometimes|array|min:1',
             'magasins.*.couleurs' => 'sometimes|array|min:1',
             'magasins.*.quantites' => 'sometimes|array|min:1',
         ]);

         // Récupération du produit à mettre à jour
         $produit = Produits::findOrFail($id);

         // Mise à jour des champs du produit si présents dans la requête
         $produit->fill($request->all());
         $produit->save();

         // Traitement des images

         // Traitement de la vidéo
         if ($request->hasFile('image_url')) {
             $video = $request->file('image_url');
             $videoName = $video->getClientOriginalName();
             $video->storeAs('public/videos', $videoName); // Stockage de la vidéo dans le dossier "storage/app/public/videos"
             $produit->image_url = Storage::url('videos/' . $videoName); // Assurez-vous d'ajuster cela selon votre modèle
             $produit->save();
         }

         if ($request->hasFile('images')) {
             $image = $request->file('images');
             foreach ($request->file('images') as $imageFile) {
                 $imageName = $imageFile->getClientOriginalName();
                 $imageFile->storeAs('public/images', $imageName); // Stockage de l'image dans le dossier "storage/app/public/images"

                 // Création de l'enregistrement d'image dans la table images
                 $image = new images();
                 $image->chemin_image = 'public/images/' . $imageName; // Chemin d'accès relatif à l'image
                 $image->produit_id = $produit->id; // ID du produit associé à cette image
                 $image->save();
             }
         }

         // Pour chaque magasin
         if ($request->has('magasins')) {
             foreach ($request->magasins as $magasinData) {
                 // Vérifiez si le magasin existe déjà
                 $magasin = Magasins::where('nom', $magasinData['nom'])
                     ->where('adresse', $magasinData['adresse'])
                     ->where('ville', $magasinData['ville'])
                     ->where('code_postal', $magasinData['code_postal'])
                     ->where('responsable', $magasinData['responsable'])
                     ->where('telephone', $magasinData['telephone'])
                     ->first();

                 if (!$magasin) {
                     // Le magasin n'existe pas encore, alors créez-le
                     $magasin = Magasins::create([
                         'nom' => $magasinData['nom'],
                         'adresse' => $magasinData['adresse'],
                         'ville' => $magasinData['ville'],
                         'code_postal' => $magasinData['code_postal'],
                         'responsable' => $magasinData['responsable'],
                         'telephone' => $magasinData['telephone']
                     ]);
                 }

                 // Pour chaque couleur, taille et quantité associée à ce magasin
                 foreach ($magasinData['couleurs'] as $index => $couleur) {
                     // Vérifiez et créez la couleur si elle n'existe pas déjà
                     $nouvelleCouleur = Couleurs::firstOrCreate(['nom' => $couleur]);

                     // Vérifiez et créez la taille si elle n'existe pas déjà
                     $nouvelleTaille = Tailles::firstOrCreate(['nom' => $magasinData['tailles'][$index]]);

                     // Créez une nouvelle quantité disponible pour ce produit, cette couleur, cette taille et ce magasin
                     $quantite = new Quantite_disponible();
                     $quantite->magasin_id = $magasin->id;
                     $quantite->produits_id = $produit->id;
                     $quantite->couleurs_id = $nouvelleCouleur->id;
                     $quantite->tailles_id = $nouvelleTaille->id;
                     $quantite->quantite = $magasinData['quantites'][$index];
                     $quantite->save();
                 }
             }
         }

         // Redirection vers une page de succès ou d'accueil
         return response()->json(['message' => 'Produit mis à jour avec succès', 'produit' => $produit], 200);
     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
         // Le produit avec l'ID spécifié n'existe pas
         return response()->json(['error' => 'Produit non trouvé'], 404);
     } catch (\Illuminate\Validation\ValidationException $e) {
         // Gérer l'erreur de validation
         return response()->json(['error' => $e->validator->errors()], 400);
     } catch (\Exception $e) {
         // Gérer toute autre exception
         return response()->json(['error' => $e->getMessage()], 500);
     }
 }



 public function supprimerProduit($id)
 {
     try {
         $produit = Produits::find($id);

         if (!$produit) {
             return response()->json(['error' => 'Produit non trouvé'], 404);
         }

         // Supprimer les images associées au produit (si nécessaire)
         $produit->images()->delete();

         // Supprimer les quantités disponibles associées au produit
         $produit->quantites()->delete();

         // Supprimer le produit lui-même
         $produit->delete();

         return response()->json(['message' => 'Produit supprimé avec succès'], 200);
     } catch (\Exception $e) {
         return response()->json(['error' => $e->getMessage()], 500);
     }
 }



    public function ajouterPromos($idProduit, Request $request)
    {
        $produit = Produits::findOrFail($idProduit);

        // Validation des données de la promotion
        $request->validate([
            'nom' => 'required|string',
            'pourcentage_reduction' => 'required|numeric',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
        ]);

        // Création de la nouvelle promotion
        $promotion = Promos::create([
            'nom' => $request->nom,
            'pourcentage_reduction' => $request->pourcentage_reduction,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
        ]);

        // Calcul du nouveau prix du produit en fonction de la promotion
        $nouveauPrix = $produit->prix_initial * (1 - $request->pourcentage_reduction / 100);

        // Mise à jour du produit avec la promotion et le nouveau prix
        $produit->promo_id = $promotion->id;
        $produit->prix = $nouveauPrix;
        $produit->save();

        return response()->json('Promotion ajoutée avec succès au produit.', 200);
    }
    public function updatePromos($idProduit, $idPromos, Request $request)
    {
        $produit = Produits::findOrFail($idProduit);

        // Recherche de la promotion associée au produit
      //  $promotion = Promos::findOrFail($idPromos);

        // Validation des données de la promotion
        $request->validate([
            'nom' => 'required|string',
            'pourcentage_reduction' => 'required|numeric',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
        ]);

        // Mise à jour des champs de la promotion
        $promotion->update([
            'nom' => $request->nom,
            'pourcentage_reduction' => $request->pourcentage_reduction,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
        ]);

        // Calcul du nouveau prix du produit en fonction de la promotion mise à jour
        $nouveauPrix = $produit->prix_initial * (1 - $request->pourcentage_reduction / 100);

        // Mise à jour du produit avec la promotion mise à jour et le nouveau prix
        $produit->promo_id = $promotion->id;
        $produit->prix = $nouveauPrix;
        $produit->save();

        return response()->json('Promotion mise à jour avec succès pour le produit.', 200);
    }

    public function updateOrCreatePromos($idProduit, Request $request)
    {
        $produit = Produits::findOrFail($idProduit);

        // Validation des données de la promotion
        $request->validate([
            'nom' => 'required|string',
            'pourcentage_reduction' => 'required|numeric',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
        ]);

        // Calcul du nouveau prix du produit en fonction de la promotion
        $nouveauPrix = $produit->prix_initial * (1 - $request->pourcentage_reduction / 100);

        // Mettre à jour ou créer une promotion
        $promotion = Promos::updateOrCreate(
            [
                'nom' => $request->nom,
                'pourcentage_reduction' => $request->pourcentage_reduction,
                'date_debut' => $request->date_debut,
                'date_fin' => $request->date_fin,
            ],
            [
                // Les autres champs de la promotion à mettre à jour ou créer si nécessaire
            ]
        );

        // Associer la promotion au produit et mettre à jour le prix
        $produit->promo_id = $promotion->id;
        $produit->prix = $nouveauPrix;
        $produit->save();

        return response()->json('Promotion mise à jour ou créée avec succès pour le produit.', 200);
    }
    public function removePromos($idProduit)
    {
        $produit = Produits::findOrFail($idProduit);

        // Récupérer le prix initial
        $prixInitial = $produit->prix_initial;

        // Vérifier si le produit est associé à une promotion
        if ($produit->promo_id) {
            // Retirer l'association de la promotion en mettant à jour le champ promo_id à NULL
            $produit->promo_id = null;

            // Réinitialiser le prix du produit à son prix initial
            $produit->prix = $prixInitial;

            // Vérifier si le prix initial est défini, sinon définir une valeur par défaut
            if ($prixInitial === null) {
                $produit->prix = 0; // Par exemple, définir le prix à 0 si le prix initial est null
            }

            // Sauvegarder les changements
            $produit->save();

            return response()->json([
                'message' => 'L\'association de la promotion au produit a été retirée avec succès.',
                'prix' => $produit->prix,
            ], 200);
        } else {
            // Si le produit n'est pas associé à une promotion, renvoyer le prix initial
            return response()->json([
                'message' => 'Le produit n\'est pas associé à une promotion.',
                'prix' => $prixInitial,
            ], 404);
        }
    }

    public function afficherTousLesProduits()
    {
        try {
            $produits = Produits::all(); // Récupère tous les produits depuis la base de données

            return response()->json(['produits' => $produits], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function produitParCategorie($categorieId)
    {
        $categorie =Categories::find($categorieId);
        $produits = Produits::where('categorie_id', $categorieId)->get();
        return response()->json([
            'categorie' => $categorie,
            'produit' => $produits,
        ]);}
    public function produitsParGenre($genreId)
    {
        $genre = Genre::find($genreId);
        $produits = Produits::where('genre_id', $genreId)->get();
        return response()->json([
            'genre' => $genre,
            'produits' => $produits,
        ]);
    }
    public function produitsParGenreEtCategorie($genreId, $categorieId)
    {
        $genre = Genre::find($genreId);
        $categories = Categories::find($categorieId);

        $produits = Produits::where('genre_id', $genreId)
                            ->where('categorie_id', $categorieId)
                            ->get();

        return response()->json([
            'genre' => $genre,
            'categories' => $categories,
            'produits' => $produits,
        ]);
    }
    public function nouveauxProduits()
{
    $nouveauxProduits = Produits::latest()->get();
    return response()->json([
        'nouveauxProduits' => $nouveauxProduits,
    ]);
}


public function searchBySousCategorie($sousCategorieId)
{
    $produits = Produits::searchBySousCategorie($sousCategorieId)->get();

    // Faites quelque chose avec les produits trouvés
    return response()->json(['produits' => $produits]);
}

public function produitsParMotCle(Request $request)
{
    // Récupérer la valeur du paramètre motCle depuis la requête
    $motCle = $request->input('motCle');

    // Vérifier si le paramètre motCle est présent
    if($motCle) {
        // Recherchez les produits correspondant au mot-clé
        $produits = Produits::whereRaw('FIND_IN_SET(?, mots_cles)', [$motCle])->get();

        return response()->json([
            'produits' => $produits,
        ]);

    } else {
        // Si le paramètre motCle n'est pas fourni, renvoyer un message d'erreur
        return response()->json([
            'message' => 'Le paramètre motCle est requis.'
        ], 400); // 400 signifie "Bad Request"
    }

}
public function index(Request $request)
{
    $query = Produits::query();

    if ($request->has('category')) {
        $query->filterByCategory($request->input('category'));
    }

    if ($request->has('sous_categorie')) {
        $query->searchBySousCategorie($request->input('sous_categorie'));
    }

    if ($request->has('genre')) {
        $query->filterByGenre($request->input('genre'));
    }

    if ($request->has('min_price') && $request->has('max_price')) {
        $query->filterByPriceRange($request->input('min_price'), $request->input('max_price'));
    }

    if ($request->has('color')) {
        $query->filterByColor($request->input('color'));
    }

    if ($request->has('size')) {
        $query->filterBySize($request->input('size'));
    }

    if ($request->has('keyword')) {
        $query->filterByKeyword($request->input('keyword'));
    }

    $produits = $query->get();

    return response()->json($produits);
}

}
