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

class MagasinsController extends Controller
{
    // Display a listing of the magasins
    public function index(Request $request)
    {
        $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get the current page and number of items per page from the request
        $perPage = $request->query('perPage', 10); // Default to 10 items per page
        $page = $request->query('page', 1); // Default to the first page

        // Fetch paginated magasins
        $magasins = Magasins::paginate($perPage);

        return response()->json($magasins);
    }

    // Store a newly created magasin in storage
    public function store(Request $request)
    { $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'nom' => 'required|string|max:255',
            'adresse' => 'required|string|max:255',
            'ville' => 'required|string|max:255',
            'code_postal' => 'required|string|max:10',
            'responsable' => 'required|string|max:255',
            'telephone' => 'required|string|max:20',
        ]);

        $magasin = Magasins::create($request->all());
        return response()->json(['message' => 'Magasin créé avec succès.', 'magasin' => $magasin], 201);
    }

    // Display the specified magasin
    public function show($id)
    { $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $magasin = Magasins::find($id);

        if (!$magasin) {
            return response()->json(['message' => 'Magasin non trouvé.'], 404);
        }

        return response()->json($magasin);
    }

    // Update the specified magasin in storage
    public function update(Request $request, $id)
    { $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'adresse' => 'somertimes|string|max:255',
            'ville' => 'sometimes|string|max:255',
            'code_postal' => 'sometimes|string|max:10',
            'responsable' => 'sometimes|string|max:255',
            'telephone' => 'sometimes|string|max:20',
        ]);

        $magasin = Magasins::find($id);

        if (!$magasin) {
            return response()->json(['message' => 'Magasin non trouvé.'], 404);
        }

        $magasin->update($request->all());
        return response()->json(['message' => 'Magasin mis à jour avec succès.', 'magasin' => $magasin]);
    }

    // Remove the specified magasin from storage
    public function destroy($id)
    { $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $magasin = Magasins::find($id);

        if (!$magasin) {
            return response()->json(['message' => 'Magasin non trouvé.'], 404);
        }

        $magasin->delete();
        return response()->json(['message' => 'Magasin supprimé avec succès.']);
    }


    public function getProductsByMagasin($magasin_id)
    {
        $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        // Check if the user is authenticated and has one of the required roles
        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Récupérer le magasin avec l'ID spécifié, ainsi que ses produits, tailles, couleurs, et quantités
        $magasin = Magasins::with('produits')->find($magasin_id);

        // Vérifier si le magasin existe
        if (!$magasin) {
            return response()->json(['message' => 'Magasin not found'], 404);
        }

        // Transformer les données pour les retourner dans un format spécifique
        $products = $magasin->produits->map(function ($produit) {
            // Récupérer les tailles, couleurs et quantités disponibles pour chaque produit
            return [
                'nom_produit' => $produit->nom_produit,
                'taille' => $produit->taille,  // Taille provenant de la jointure avec `tailles`
                'couleur' => $produit->couleur,  // Couleur provenant de la jointure avec `couleurs`
                'quantite' => $produit->pivot->quantite  // Quantité provenant de la table pivot `quantite`
            ];
        });

        // Retourner la réponse avec les produits du magasin
        return response()->json([
            'magasin' => $magasin->nom,
            'produits' => $products
        ]);
    }
}


