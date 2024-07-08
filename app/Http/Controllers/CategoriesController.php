<?php

namespace App\Http\Controllers;
use App\Models\Categories;

use Illuminate\Http\Request;


    class CategoriesController extends Controller
    {
        // Afficher toutes les catégories
        public function index()
        {
            $categories = Categories::all();
            return response()->json($categories);
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
            $categorie = Categories::create($request->all());
            return response()->json($categorie, 201);
        }

        // Mettre à jour une catégorie
        public function update(Request $request, $id)
        {
            $categorie = Categories::findOrFail($id);
            $categorie->update($request->all());
            return response()->json($categorie, 200);
        }

        // Supprimer une catégorie
        public function destroy($id)
        {
            Categories::findOrFail($id)->delete();
            return response()->json(null, 204);
        }
    }





