<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\categories;
use App\Models\sous_categories;
use App\Models\souscategories;

use Illuminate\Support\Facades\Auth;

class SousCategorieController extends Controller
{


    public function store(Request $request)
    {
        $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validation des champs
        $request->validate([
            'nom' => 'required|string',
            'categorie_nom' => 'required|string', // Validation pour le nom de la catégorie
        ]);

        // Récupérer ou créer la catégorie par son nom
        $categorie = Categories::firstOrCreate(
            ['nom' => $request->input('categorie_nom')],
            ['nom' => $request->input('categorie_nom')]
        );

        // Créer la sous-catégorie
        $sousCategorie = SousCategories::create([
            'categorie_id' => $categorie->id,
            'nom' => $request->input('nom'),
        ]);

        return response()->json($sousCategorie, 201);
    }

    public function update(Request $request, $id)
    {
        // Authentification de l'utilisateur
        $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        // Vérification des rôles de l'utilisateur
        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validation des champs
        $request->validate([
            'nom' => 'required|string',
            'categorie_nom' => 'nullable|string', // Validation pour le nom de la catégorie
        ]);

        // Initialiser l'ID de la catégorie
        $categorieId = null;

        // Vérifiez si le champ categorie_nom est rempli
        if ($request->filled('categorie_nom')) {
            // Récupérer ou créer la catégorie par son nom
            $categorie = Categories::firstOrCreate(
                ['nom' => $request->input('categorie_nom')],
                ['nom' => $request->input('categorie_nom')]
            );
            $categorieId = $categorie->id; // Obtenez l'ID de la catégorie
        }

        // Trouver la sous-catégorie à mettre à jour
        $sousCategorie = Souscategories::findOrFail($id);

        // Mettre à jour la sous-catégorie
        $sousCategorie->update([
            'categorie_id' => $categorieId, // Utiliser l'ID de catégorie ou null si catégorie_nom est vide
            'nom' => $request->input('nom'),
        ]);

        return response()->json($sousCategorie, 200);
    }


    public function index(Request $request)
    {
        // Get the number of items per page from the query string, default to 10
        $perPage = $request->input('per_page', 10);

        // Get the page number from the query string, default to 1
        $currentPage = $request->input('page', 1);

        // Fetch categories with their subcategories, using pagination
        $categories = Categories::with('sousCategories')
            ->paginate($perPage);

        return response()->json([
            'data' => $categories->items(),
            'total' => $categories->total(),
            'current_page' => $categories->currentPage(),
            'last_page' => $categories->lastPage(),
            'per_page' => $categories->perPage(),
        ]);
    }
    public function index1(Request $request)
    {
        // Fetch all categories with their corresponding subcategories
        $categories = Categories::with('sousCategories')->get();
// dd();
        return response()->json([
            'data' => $categories, // Retourne toutes les catégories avec leurs sous-catégories
        ]);
    }

public function show($id)
{
    $sousCategorie = SousCategories::findOrFail($id);
    return response()->json($sousCategorie);
}
public function destroy($id)
{   $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    SousCategories::findOrFail($id)->delete();
    return response()->json(null, 204);
}

}
