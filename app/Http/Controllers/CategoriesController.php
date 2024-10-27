<?php

namespace App\Http\Controllers;
use App\Models\Categories;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;


    class CategoriesController extends Controller

    {




        // Afficher toutes les catégories
        public function index(Request $request)
        {
            $search = $request->input('search');
            $categoriesQuery = Categories::query();

            if ($search) {
                $categoriesQuery->where('name', 'like', '%' . $search . '%');
            }

            $categories = $categoriesQuery->get();

            return response()->json([
                'data' => $categories,
            ]);
        }

        // Afficher une seule catégorie
        public function show($id)
        {
            $categorie = Categories::findOrFail($id);
            return response()->json($categorie);
        }

        public function store(Request $request)
        {
            $user = Auth::user();
            $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

            if (!$user || !$user->hasAnyRole($roles)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validatedData = $request->validate([
                'nom' => 'required|string|max:255', // Make sure 'nom' is required
            ]);

            $categorie = Categories::create($validatedData);

            return response()->json($categorie, 201);
        }
        // Mettre à jour une catégorie
        public function update(Request $request, $id)
        {     $user = Auth::user();
            $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

            if (!$user || !$user->hasAnyRole($roles)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $categorie = Categories::findOrFail($id);
            $categorie->update($request->all());
            return response()->json($categorie, 200);
        }

        // Supprimer une catégorie
        public function destroy($id)
        {     $user = Auth::user();
            $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

            if (!$user || !$user->hasAnyRole($roles)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            Categories::findOrFail($id)->delete();
            return response()->json(null, 204);
        }
    }





