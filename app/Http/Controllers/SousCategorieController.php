<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\categories;
use App\Models\sous_categories;

use Illuminate\Support\Facades\Auth;

class SousCategorieController extends Controller
{


    public function store(Request $request)
{   $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $request->validate([
        'nom' => 'required|string',
        'categorie_id' => 'nullable|exists:categories,id',
    ]);

    $categorieId = $request->input('categorie_id');

    if ($categorieId && !Categories::find($categorieId)) {
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
{   $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $request->validate([
        'nom' => 'required|string',
        'categorie_id' => 'nullable|exists:categories,id',
    ]);

    $categorieId = $request->input('categorie_id');

    if ($categorieId && !Categories::find($categorieId)) {
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
