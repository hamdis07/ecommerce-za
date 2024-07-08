<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\categories;
use App\Models\sous_categories;


class SousCategorieController extends Controller
{
    public function store(Request $request)
{
    $request->validate([
        'nom' => 'required|string',
        'categorie_id' => 'nullable|exists:categories,id',
    ]);

    $categorieId = $request->input('categorie_id');

    // Vérifier si la catégorie existe
    if ($categorieId && !Categories::find($categorieId)) {
        // Si la catégorie n'existe pas, créer une nouvelle catégorie
        $categorie = Categories::create(['nom' => 'Nouvelle catégorie']);
        $categorieId = $categorie->id;
    }

    $sousCategorie = SousCategories::create([
        'categorie_id' => $categorieId,
        'nom' => $request->input('nom'),
    ]);

    return response()->json($sousCategorie, 201);
}
public function update(Request $request, $id)
{
    $request->validate([
        'nom' => 'required|string',
        'categorie_id' => 'nullable|exists:categories,id',
    ]);

    $categorieId = $request->input('categorie_id');

    // Vérifier si la catégorie existe
    if ($categorieId && !Categories::find($categorieId)) {
        // Si la catégorie n'existe pas, créer une nouvelle catégorie
        $categorie = Categories::create(['nom' => 'Nouvelle catégorie']);
        $categorieId = $categorie->id;
    }

    $sousCategorie = Souscategories::findOrFail($id);
    $sousCategorie->update([
        'categorie_id' => $categorieId,
        'nom' => $request->input('nom'),
    ]);

    return response()->json($sousCategorie, 200);
}
public function index()
{
    $sousCategories = SousCategories::all();
    return response()->json($sousCategories);
}

// Afficher une seule sous-catégorie
public function show($id)
{
    $sousCategorie = SousCategories::findOrFail($id);
    return response()->json($sousCategorie);
}
public function destroy($id)
{
    SousCategories::findOrFail($id)->delete();
    return response()->json(null, 204);
}

}
