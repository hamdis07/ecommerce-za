<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\commandes;
use App\Models\commandesproduit;

use App\Models\Produits;
use App\Models\Categories;
use App\Models\Genre;
use App\Models\SousCategories;
use App\Models\Tailles;
use App\Models\Couleurs;
use App\Models\Promos;
use App\Models\images;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

use App\Models\Quantitedisponible;
use App\Models\Magasins;
class ProduitsController extends Controller
{


    public function nouveauProduit(Request $request)
    { $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        try {

            $request->validate([
                'references' => 'required|string',
                'nom_produit' => 'required|string',
                'images' => 'required|array|min:1',
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'image_url' => 'nullable|file|mimetypes:image/jpeg,image/png,image/jpg,image/gif,video/mp4,video/mpeg,video/quicktime|max:204800',
                'description' => 'required|string',
                'prix_initial' => 'required|numeric|min:0',
                'composition' => 'required|string',
                'entretien' => 'required|string',
                'genre' => 'required|string',
                'categorie' => 'required|string',
                'sous_categorie' => 'required|string',
                'mots_cles' => 'nullable|string',
                'magasins' => 'required|array|min:1',
                'magasins.*.nom' => 'required|string',
                'magasins.*.adresse' => 'required|string',
                'magasins.*.ville' => 'required|string',
                'magasins.*.code_postal' => 'required|string',
                'magasins.*.responsable' => 'required|string',
                'magasins.*.telephone' => 'required|string',
                'magasins.*.tailles' => 'required|array|min:1',
                'magasins.*.tailles.*.nom' => 'required|string',
                'magasins.*.tailles.*.couleurs' => 'required|array|min:1',
                'magasins.*.tailles.*.couleurs.*.nom' => 'required|string',
                'magasins.*.tailles.*.couleurs.*.quantite' => 'required|numeric|min:1',
                'promo.nom' => 'nullable|string',
                'promo.pourcentage_reduction' => 'nullable|numeric',
                'promo.date_debut' => 'nullable|date',
                'promo.date_fin' => 'nullable|date|after:promo.date_debut',
            ]);
            \Log::info('Début de la création du produit');

            $genre = Genre::firstOrCreate(['nom' => $request->genre]);
            $categorie = Categories::firstOrCreate(['nom' => $request->categorie]);
            $sousCategorie = SousCategories::firstOrCreate(['nom' => $request->sous_categorie, 'categorie_id' => $categorie->id]);

            $produit = Produits::create([
                'references' => $request->references,
                'nom_produit' => $request->nom_produit,
                'description' => $request->description,
                'composition' => $request->composition,
                'entretien' => $request->entretien,
                'prix_initial' => $request->prix_initial,
                'prix' => $request->prix_initial,
                'image_url' => $request->image_url,
                'mots_cles' => $request->mots_cles
            ]);

            $produit->genre_id = $genre->id;
            $produit->categorie_id = $categorie->id;
            $produit->souscategories_id = $sousCategorie->id;
            $produit->save();

            \Log::info('Produit sauvegardé avec ID: ' . $produit->id);
            if ($request->hasFile('images')) {
                $images = $request->file('images');

                // Vérifiez si c'est bien un tableau
                if (is_array($images)) {
                    foreach ($images as $imageFile) {
                        if ($imageFile->isValid()) {
                            $imageName = time() . '_' . $imageFile->getClientOriginalName();
                            $imagePath = $imageFile->move(public_path('images'), $imageName);
                            $imageUrl = asset('images/' . $imageName);

                            \Log::info('Image sauvegardée: ' . $imageUrl);

                            Images::create([
                                'chemin_image' => $imageUrl,
                                'produit_id' => $produit->id
                            ]);
                        } else {
                            \Log::warning('Fichier image invalide.');
                        }
                    }
                } else {
                    \Log::warning('Le champ images n\'est pas un tableau.');
                }}
            if ($request->hasFile('image_url')) {
                $video = $request->file('image_url');
                $videoName = time() . '_' . $video->getClientOriginalName();
                $video->move(public_path('videos'), $videoName);
                $produit->image_url = asset('videos/' . $videoName);  // Use asset() to get the public URL
                $produit->save();
            }

            \Log::info('Traitement des magasins commencé');
            foreach ($request->magasins as $magasinData) {
                $magasin = Magasins::firstOrCreate([
                    'nom' => $magasinData['nom'],
                    'adresse' => $magasinData['adresse'],
                    'ville' => $magasinData['ville'],
                    'code_postal' => $magasinData['code_postal'],
                    'responsable' => $magasinData['responsable'],
                    'telephone' => $magasinData['telephone']
                ]);

                foreach ($magasinData['tailles'] as $tailleData) {
                    $nouvelleTaille = Tailles::firstOrCreate(['nom' => $tailleData['nom']]);

                    foreach ($tailleData['couleurs'] as $couleurData) {
                        $nouvelleCouleur = Couleurs::firstOrCreate(['nom' => $couleurData['nom']]);

                        Quantitedisponible::create([
                            'magasin_id' => $magasin->id,
                            'produits_id' => $produit->id,
                            'couleurs_id' => $nouvelleCouleur->id,
                            'tailles_id' => $nouvelleTaille->id,
                            'quantite' => $couleurData['quantite']
                        ]);
                    }
                }
            }
            \Log::info('Données reçues pour mise à jour : ', $request->all());

            if ($request->has('promo.nom') && $request->has('promo.pourcentage_reduction') && $request->has('promo.date_debut') && $request->has('promo.date_fin')) {
                $promotion = Promos::firstOrCreate([
                    'nom' => $request->promo['nom'],
                    'pourcentage_reduction' => $request->promo['pourcentage_reduction'],
                    'date_debut' => $request->promo['date_debut'],
                    'date_fin' => $request->promo['date_fin']
                ]);

                $produit->promo_id = $promotion->id;

                $currentDate = now();

                if ($currentDate->between($promotion->date_debut, $promotion->date_fin)) {
                    $produit->prix = $produit->prix_initial * (1 - $promotion->pourcentage_reduction / 100);
                } else {
                    $produit->prix = $produit->prix_initial;
                }
                $produit->save();
            }

            return response()->json(['message' => 'Produit ajouté avec succès', 'produit' => $produit], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->validator->errors()], 400);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création du produit: ' . $e->getMessage());
            return response()->json(['error' => 'Une erreur est survenue lors de la création du produit'], 500);
        }
    }



    public function modifierProduit(Request $request, $id)
    { $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        try {
            $produit = Produits::findOrFail($id);

            // Validation conditionnelle
            $request->validate([
                'references' => 'nullable|string',
                'nom_produit' => 'nullable|string',
                'images' => 'nullable|array',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'image_url' => 'nullable|file|mimetypes:image/jpeg,image/png,image/jpg,image/gif,video/mp4,video/mpeg,video/quicktime|max:204800',
                'description' => 'nullable|string',
                'prix_initial' => 'nullable|numeric|min:0',
                'composition' => 'nullable|string',
                'entretien' => 'nullable|string',
                'genre' => 'nullable|string',
                'categorie' => 'nullable|string',
                'sous_categorie' => 'nullable|string',
                'mots_cles' => 'nullable|string',
                'magasins' => 'nullable|array',
                'magasins.*.nom' => 'nullable|string',
                'magasins.*.adresse' => 'nullable|string',
                'magasins.*.ville' => 'nullable|string',
                'magasins.*.code_postal' => 'nullable|string',
                'magasins.*.responsable' => 'nullable|string',
                'magasins.*.telephone' => 'nullable|string',
                'magasins.*.tailles' => 'nullable|array',
                'magasins.*.tailles.*.nom' => 'nullable|string',
                'magasins.*.tailles.*.couleurs' => 'nullable|array',
                'magasins.*.tailles.*.couleurs.*.nom' => 'nullable|string',
                'magasins.*.tailles.*.couleurs.*.quantite' => 'nullable|numeric|min:1',
                'promo.nom' => 'nullable|string',
                'promo.pourcentage_reduction' => 'nullable|numeric',
                'promo.date_debut' => 'nullable|date',
                'promo.date_fin' => 'nullable|date|after:promo.date_debut',
            ]);

           // \Log::info('Début de la mise à jour du produit');

            // Mise à jour des attributs du produit
            $produit->update([
                'references' => $request->input('references', $produit->references),
                'nom_produit' => $request->input('nom_produit', $produit->nom_produit),
                'description' => $request->input('description', $produit->description),
                'composition' => $request->input('composition', $produit->composition),
                'entretien' => $request->input('entretien', $produit->entretien),
                'prix_initial' => $request->input('prix_initial', $produit->prix_initial),
                'prix' => $request->input('prix_initial', $produit->prix_initial), // Set 'prix' to 'prix_initial'
                'image_url' => $request->input('image_url', $produit->image_url),
                'mots_cles' => $request->input('mots_cles', $produit->mots_cles),
            ]);

            // Assignation des IDs de genre, catégorie et sous-catégorie si fournis
            if ($request->has('genre')) {
                $genre = Genre::firstOrCreate(['nom' => $request->genre]);
                $produit->genre_id = $genre->id;
            }
            if ($request->has('categorie')) {
                $categorie = Categories::firstOrCreate(['nom' => $request->categorie]);
                $produit->categorie_id = $categorie->id;
            }
            if ($request->has('sous_categorie')) {
                $sousCategorie = SousCategories::firstOrCreate(['nom' => $request->sous_categorie, 'categorie_id' => $produit->categorie_id]);
                $produit->souscategories_id = $sousCategorie->id;
            }
            $produit->save();

            \Log::info('Produit mis à jour avec ID: ' . $produit->id);

            // Traitement des images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    $imageName = time() . '_' . $imageFile->getClientOriginalName();
                    $imagePath = $imageFile->storeAs('public/images', $imageName);
                    \Log::info('Image sauvegardée: ' . $imageName);

                    Images::updateOrCreate(
                        ['produit_id' => $produit->id, 'chemin_image' => 'public/images/' . $imageName],
                        ['produit_id' => $produit->id]
                    );
                    \Log::info('Image enregistrée dans la base de données: ' . $imageName);
                }
            }

            // Traitement de l'image ou vidéo URL
            if ($request->hasFile('image_url')) {
                $video = $request->file('image_url');
                $videoName = time() . '_' . $video->getClientOriginalName();
                $video->storeAs('public/videos', $videoName);
                $produit->image_url = Storage::url('videos/' . $videoName);
                $produit->save();
            }

            \Log::info('Traitement des magasins commencé');
            // Suppression des magasins existants
            Quantitedisponible::where('produits_id', $produit->id)->delete();

            if ($request->has('magasins')) {
                foreach ($request->magasins as $magasinData) {
                    $magasin = Magasins::updateOrCreate([
                        'nom' => $magasinData['nom'] ?? '',
                        'adresse' => $magasinData['adresse'] ?? '',
                        'ville' => $magasinData['ville'] ?? '',
                        'code_postal' => $magasinData['code_postal'] ?? '',
                        'responsable' => $magasinData['responsable'] ?? '',
                        'telephone' => $magasinData['telephone'] ?? ''
                    ]);

                    if (isset($magasinData['tailles'])) {
                        foreach ($magasinData['tailles'] as $tailleData) {
                            $nouvelleTaille = Tailles::firstOrCreate(['nom' => $tailleData['nom']]);

                            if (isset($tailleData['couleurs'])) {
                                foreach ($tailleData['couleurs'] as $couleurData) {
                                    $nouvelleCouleur = Couleurs::firstOrCreate(['nom' => $couleurData['nom']]);

                                    Quantitedisponible::updateOrCreate([
                                        'magasin_id' => $magasin->id,
                                        'produits_id' => $produit->id,
                                        'couleurs_id' => $nouvelleCouleur->id,
                                        'tailles_id' => $nouvelleTaille->id,
                                    ], [
                                        'quantite' => $couleurData['quantite']
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

        //    \Log::info('Données reçues pour mise à jour : ', $request->all());

            // Traitement de la promotion
            if ($request->has('promo')) {
                $promotion = Promos::updateOrCreate(
                    ['id' => $produit->promo_id ?? null],
                    $request->promo
                );

                $produit->promo_id = $promotion->id;

                // Mettre à jour le prix en fonction des dates de promotion
                $currentDate = now();

                if ($currentDate->between($promotion->date_debut, $promotion->date_fin)) {
                    $produit->prix = $produit->prix_initial * (1 - $promotion->pourcentage_reduction / 100);
                } else {
                    $produit->prix = $produit->prix_initial;
                }
                $produit->save();
            }

            return response()->json(['message' => 'Produit mis à jour avec succès', 'produit' => $produit], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->validator->errors()], 400);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour du produit: ' . $e->getMessage());
            return response()->json(['error' => 'Une erreur est survenue lors de la mise à jour du produit'], 500);
        }
    }



 public function supprimerProduit($id)
 { $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
     try {
         $produit = Produits::find($id);

         if (!$produit) {
             return response()->json(['error' => 'Produit non trouvé'], 404);
         }

         $produit->images()->delete();

         $produit->quantitedisponible()->delete();

         $produit->delete();

         return response()->json(['message' => 'Produit supprimé avec succès'], 200);
     } catch (\Exception $e) {
         return response()->json(['error' => $e->getMessage()], 500);
     }
 }
 public function afficherTousLesProduits(Request $request)
{
    $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    try {
        // Get the page size from the request, or use a default of 10
        $perPage = $request->input('per_page', 10);

        // Get paginated products
        $produits = Produits::with([
            'quantitedisponible.magasin',
            'quantitedisponible.tailles',
            'quantitedisponible.couleurs',
            'images',
            'categories',
            'genre',
            'souscategories'
        ])->paginate($perPage); // Apply pagination

        // Map the products and related details
        $produitsAvecDetails = $produits->map(function ($produit) {
            $quantites = $produit->quantitedisponible ? $produit->quantitedisponible->map(function ($quantite) {
                return [
                    'magasin' => $quantite->magasin->nom ?? null,
                    'quantite' => $quantite->quantite,
                    'taille' => $quantite->tailles->nom ?? null,
                    'couleur' => $quantite->couleurs->nom ?? null,
                ];
            }) : [];

            $images = $produit->images ? $produit->images->map(function ($image) {
                return [
                    'url' => asset('storage/app/public/videos' . $image->chemin_image), // Full image path
                    'alt' => $image->alt_text ?? 'Image du produit', // Default alt text
                ];
            }) : [];

            return [
                'produit' => $produit,
                'categories' => $produit->categories ? $produit->categories->nom : 'Non spécifié',
                'souscategories' => $produit->souscategories ? $produit->souscategories->nom : 'Non spécifié',
                'genre' => $produit->genre ? $produit->genre->nom : 'Non spécifié',
                'quantites' => $quantites,
                'images' => $images
            ];
        });

        // Return paginated data including products and pagination metadata
        return response()->json([
            'produits' => $produitsAvecDetails,
            'current_page' => $produits->currentPage(),
            'total_pages' => $produits->lastPage(),
            'total_items' => $produits->total()
        ], 200);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

 public function afficherTousLesProduitspageclient(Request $request)
 {
     try {
         $query = Produits::with([
             'quantitedisponible.magasin',
             'quantitedisponible.tailles',
             'quantitedisponible.couleurs',
             'images',
             'categories',
             'genre',
             'souscategories'
         ])
         ->where('is_hidden', false)
         ->orderBy('is_featured', 'desc'); ;

         if ($request->has('is_featured')) {
             $query->where('is_featured', $request->input('is_featured'));
         }

         $produits = $query->get();

         $produitsAvecDetails = $produits->map(function ($produit) {
             $quantites = $produit->quantitedisponible ? $produit->quantitedisponible->map(function ($quantite) {
                 return [
                     'magasin' => $quantite->magasin->nom ?? null,
                     'quantite' => $quantite->quantite,
                     'taille' => $quantite->tailles->nom ?? null,
                     'couleur' => $quantite->couleurs->nom ?? null,
                 ];
             }) : [];

             $images = $produit->images ? $produit->images->map(function ($image) {
                 return [
                     'url' => asset('storage/app/public/videos/' . $image->chemin_image),
                     'alt' => $image->alt_text ?? 'Image du produit',
                 ];
             }) : [];

             return [
                 'produit' => $produit,
                 'categories' => $produit->categories ? $produit->categories->nom : 'Non spécifié',
                 'souscategories' => $produit->souscategories ? $produit->souscategories->nom : 'Non spécifié',
                 'genre' => $produit->genre ? $produit->genre->nom : 'Non spécifié',
                 'quantites' => $quantites,
                 'images' => $images
             ];
         });

         return response()->json(['produits' => $produitsAvecDetails], 200);
     } catch (\Exception $e) {
         return response()->json(['error' => $e->getMessage()], 500);
     }}


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
            $produits = Produits::where('genre_id', $genreId)
                                ->with(['categories', 'sousCategories', 'images']) // Include related categories, subcategories, and images
                                ->get();

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
public function produitsEnPromotions()
{
    $produits = Produits::whereHas('promos', function ($query) {
        $query->where('date_debut', '<=', now())
              ->where('date_fin', '>=', now());
    })
    ->with('promos')
    ->get();

    $result = $produits->map(function ($produit) {
        return [
            'produit_id' => $produit->id,
            'nom_produit' => $produit->nom_produit,
            'prix' => $produit->prix,
            'prix_initial' => $produit->prix_initial,
            'reduction' => $produit->promos->pourcentage_reduction,
            'date_debut' => $produit->promos->date_debut,
            'date_fin' => $produit->promos->date_fin,
        ];
    });

    return response()->json(['produits_en_promotion' => $result], 200);
}
public function produitsLesPlusCommandes()
{
    $produits = Produits::select('produits.id', 'produits.nom_produit', \DB::raw('COUNT(commandesproduits.produits_id) as total_commandes'))
        ->join('commandesproduits', 'produits.id', '=', 'commandesproduits.produits_id')
        ->groupBy('produits.id', 'produits.nom_produit') // Ajoutez ici les colonnes non agrégées
        ->orderBy('total_commandes', 'desc')
        ->with('commandes')
        ->get();

    $result = $produits->map(function ($produit) {
        return [
            'produit_id' => $produit->id,
            'nom_produit' => $produit->nom_produit,
            'quantite_commandee' => $produit->total_commandes,
        ];
    });

    return response()->json(['produits_les_plus_commandes' => $result], 200);
}




public function searchBySousCategorie($sousCategorieId)
{
    $produits = Produits::searchBySousCategorie($sousCategorieId)->get();

    return response()->json(['produits' => $produits]);
}

public function produitsParMotCle(Request $request)
{
    $motCle = $request->input('motCle');

    if($motCle) {
        $produits = Produits::whereRaw('FIND_IN_SET(?, mots_cles)', [$motCle])->get();

        return response()->json([
            'produits' => $produits,
        ]);

    } else {
        return response()->json([
            'message' => 'Le paramètre motCle est requis.'
        ], 400);
    }

}
public function index(Request $request)
{
    $query = Produits::query();

    if ($request->has('categories') && $request->input('categories') !== '') {
        $query->whereHas('categories', function($q) use ($request) {
            $q->where('nom', $request->input('categories'));
        });
    }

    if ($request->has('souscategories') && $request->input('souscategories') !== '') {
        $query->whereHas('sousCategories', function($q) use ($request) {
            $q->where('nom', $request->input('souscategories'));
        });
    }

    if ($request->has('genre') && $request->input('genre') !== '') {
        $query->whereHas('genre', function($q) use ($request) {
            $q->where('nom', $request->input('genre'));
        });
    }

    if ($request->has('min_price') && $request->has('max_price') &&
        $request->input('min_price') !== '' && $request->input('max_price') !== '') {
        $query->filterByPriceRange($request->input('min_price'), $request->input('max_price'));
    }

    if ($request->has('color') && $request->input('color') !== '') {
        $query->whereHas('couleurs', function($q) use ($request) {
            $q->where('nom', $request->input('color'));
        });
    }

    if ($request->has('size') && $request->input('size') !== '') {
        $query->whereHas('tailles', function($q) use ($request) {
            $q->where('nom', $request->input('size'));
        });
    }

    if ($request->has('keyword') && $request->input('keyword') !== '') {
        $query->filterByKeyword($request->input('keyword'));
    }

    $produits = $query->get();

    return response()->json($produits);
}


public function getProduitById($id)
{
    try {

        $produit = Produits::with([
            'quantitedisponible.magasin',
            'quantitedisponible.tailles',
            'quantitedisponible.couleurs',
            'images',
            'categories',
            'genre',
            'souscategories',
            'promos'
        ])->findOrFail($id);


        $quantites = $produit->quantitedisponible ? $produit->quantitedisponible->map(function ($quantite) {
            return [
                'magasin' => $quantite->magasin->nom ?? null,
                'quantite' => $quantite->quantite,
                'taille' => $quantite->tailles->nom ?? null,
                'couleur' => $quantite->couleurs->nom ?? null,
            ];
        }) : [];


        $images = $produit->images ? $produit->images->map(function ($image) {
            return [
                'url' => asset('storage/app/public/videos' . $image->chemin_image), // Utilisation de asset() pour obtenir le chemin complet
                'alt' => $image->alt_text ?? 'Image du produit', // Texte alternatif par défaut
            ];
        }) : [];

        return response()->json([
            'produit' => $produit,
            'categories' => $produit->categories ? $produit->categories->nom : 'Non spécifié',
            'souscategories' => $produit->souscategories ? $produit->souscategories->nom : 'Non spécifié',
            'genre' => $produit->genre ? $produit->genre->nom : 'Non spécifié',
            'quantites' => $quantites,
            'images' => $images,
        ], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}












public function ajouterPromos($idProduit, Request $request)
{$user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $produit = Produits::findOrFail($idProduit);

    $request->validate([
        'nom' => 'required|string',
        'pourcentage_reduction' => 'required|numeric',
        'date_debut' => 'required|date',
        'date_fin' => 'required|date|after:date_debut',
    ]);

    $promotion = Promos::firstOrCreate([
        'nom' => $request->nom,
        'pourcentage_reduction' => $request->pourcentage_reduction,
        'date_debut' => $request->date_debut,
        'date_fin' => $request->date_fin,
    ]);


    $nouveauPrix = $produit->prix_initial * (1 - $request->pourcentage_reduction / 100);

    $produit->promo_id = $promotion->id;
    $produit->prix = $nouveauPrix;
    $produit->save();

    return response()->json('Promotion ajoutée avec succès au produit.', 200);
}

public function updatePromos($idProduit, $idPromos, Request $request)
{$user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $produit = Produits::findOrFail($idProduit);
    $promotion = Promos::findOrFail($idPromos);


    $request->validate([
        'nom' => 'required|string',
        'pourcentage_reduction' => 'required|numeric',
        'date_debut' => 'required|date',
        'date_fin' => 'required|date|after:date_debut',
    ]);


    $promotion->update([
        'nom' => $request->nom,
        'pourcentage_reduction' => $request->pourcentage_reduction,
        'date_debut' => $request->date_debut,
        'date_fin' => $request->date_fin,
    ]);

    $nouveauPrix = $produit->prix_initial * (1 - $request->pourcentage_reduction / 100);

    $produit->promo_id = $promotion->id;
    $produit->prix = $nouveauPrix;
    $produit->save();

    return response()->json('Promotion mise à jour avec succès pour le produit.', 200);
}




public function removePromos($idProduit)
{$user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $produit = Produits::findOrFail($idProduit);

    $prixInitial = $produit->prix_initial;

    if ($produit->promo_id) {
        $produit->promo_id = null;

        $produit->prix = $prixInitial ?? 0;
        $produit->save();

        return response()->json([
            'message' => 'L\'association de la promotion au produit a été retirée avec succès.',
            'prix' => $produit->prix,
        ], 200);
    } else {
        return response()->json([
            'message' => 'Le produit n\'est pas associé à une promotion.',
            'prix' => $prixInitial,
        ], 404);
    }
}
public function getPromos()
{$user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $promotions = Promos::all();
    return response()->json($promotions, 200);
}


public function applyPromosToMultipleProducts(Request $request)
{$user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $productIds = $request->input('product_ids', []); // IDs des produits à mettre à jour
    $promoData = $request->input('promo', []);

    if (count($productIds) > 0 && $this->isPromoDataValid($promoData)) {
        $promotion = Promos::firstOrCreate([
            'nom' => $promoData['nom'],
            'pourcentage_reduction' => $promoData['pourcentage_reduction'],
            'date_debut' => $promoData['date_debut'],
            'date_fin' => $promoData['date_fin']
        ]);

        foreach ($productIds as $idProduit) {
            $produit = Produits::findOrFail($idProduit);

            $produit->promo_id = $promotion->id;

            $currentDate = now();
            if ($currentDate->between($promotion->date_debut, $promotion->date_fin)) {
                $produit->prix = $produit->prix_initial * (1 - $promotion->pourcentage_reduction / 100);
            } else {
                $produit->prix = $produit->prix_initial;
            }

            $produit->save();
        }

        return response()->json('Promotion appliquée avec succès à plusieurs produits.', 200);
    } else {
        return response()->json('Données de promotion manquantes ou invalides.', 400);
    }
}

private function isPromoDataValid($promoData)
{
    return isset($promoData['nom']) &&
           isset($promoData['pourcentage_reduction']) &&
           isset($promoData['date_debut']) &&
           isset($promoData['date_fin']) &&
           is_numeric($promoData['pourcentage_reduction']) &&
           $promoData['pourcentage_reduction'] > 0 && $promoData['pourcentage_reduction'] <= 100 && // Vérification du pourcentage
           strtotime($promoData['date_debut']) &&
           strtotime($promoData['date_fin']);
}

public function featureProduct($idProduit)
{$user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $produit = Produits::findOrFail($idProduit);
    $produit->is_featured = true;
    $produit->save();

    return response()->json(['message' => 'Le produit a été mis en avant.'], 200);
}
public function hideProduct($idProduit)
{$user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $produit = Produits::findOrFail($idProduit);
    $produit->is_hidden = true;
    $produit->save();

    return response()->json(['message' => 'Le produit a été masqué.'], 200);
}

public function unfeatureProduct($idProduit)
{$user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $produit = Produits::findOrFail($idProduit);
    $produit->is_featured = false;
    $produit->save();

    return response()->json(['message' => 'Le produit n\'est plus mis en avant.'], 200);
}

public function unhideProduct($idProduit)
{$user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $produit = Produits::findOrFail($idProduit);
    $produit->is_hidden = false;
    $produit->save();

    return response()->json(['message' => 'Le produit a été réaffiché.'], 200);
}






}
//public function updateOrCreatePromos($idProduit, Request $request)
// {$user = Auth::user();
//     $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

//     if (!$user || !$user->hasAnyRole($roles)) {
//         return response()->json(['message' => 'Unauthorized'], 403);
//     }
//     $produit = Produits::findOrFail($idProduit);

//     $request->validate([
//         'nom' => 'required|string',
//         'pourcentage_reduction' => 'required|numeric',
//         'date_debut' => 'required|date',
//         'date_fin' => 'required|date|after:date_debut',
//     ]);

//     $promotion = Promos::updateOrCreate(
//         [
//             'nom' => $request->nom,
//             'pourcentage_reduction' => $request->pourcentage_reduction,
//             'date_debut' => $request->date_debut,
//             'date_fin' => $request->date_fin,
//         ]
//     );

//     $nouveauPrix = $produit->prix_initial * (1 - $request->pourcentage_reduction / 100);

//     $produit->promo_id = $promotion->id;
//     $produit->prix = $nouveauPrix;
//     $produit->save();

//     return response()->json('Promotion mise à jour ou créée avec succès pour le produit.', 200);
// }
