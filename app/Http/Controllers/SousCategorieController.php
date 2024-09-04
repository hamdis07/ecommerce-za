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

        // Trouver la sous-catégorie à mettre à jour
        $sousCategorie = Souscategories::findOrFail($id);

        // Mettre à jour la sous-catégorie
        $sousCategorie->update([
            'categorie_id' => $categorie->id,
            'nom' => $request->input('nom'),
        ]);

        return response()->json($sousCategorie, 200);
    }

    public function index()
    {
        $categories = Categories::with('sousCategories')->get();

        return response()->json($categories);
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
