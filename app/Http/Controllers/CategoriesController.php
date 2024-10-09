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
            $perPage = $request->input('perPage', 10); // Par défaut, 10 éléments par page
            $categoriesQuery = Categories::query();

            // Appliquer la recherche si elle est présente
            if ($search) {
                $categoriesQuery->where('name', 'like', '%' . $search . '%');
            }

            $categories = $categoriesQuery->paginate($perPage);

            return response()->json([
                'data' => $categories->items(),
                'currentPage' => $categories->currentPage(),
                'totalPages' => $categories->lastPage(),
                'totalItems' => $categories->total(),
            ]);
        }

        // Afficher une seule catégorie
        public function show($id)
        {
            $categorie = Categories::findOrFail($id);
            return response()->json($categorie);
        }

        // Enregistrer une nouvelle catégorie
        public function store(Request $request)
        {
            // Check if the user is authenticated and has the required roles
            $user = Auth::user();
            $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

            if (!$user || !$user->hasAnyRole($roles)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Validate the incoming request data
            $validatedData = $request->validate([
                'nom' => 'required|string|max:255', // Make sure 'nom' is required
            ]);

            // Create the category using the validated data
            $categorie = Categories::create($validatedData);

            // Return the created category with a success response
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





