<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;



use App\Models\Genre;
use Illuminate\Http\Request;

class GenresController extends Controller
{
   
    // Afficher tous les genres
    public function index()
    {
        $genres = Genre::all();
        return response()->json($genres);
    }

    // Afficher un seul genre
    public function show($id)
    {
        $genre = Genre::findOrFail($id);
        return response()->json($genre);
    }

    // Enregistrer un nouveau genre
    public function store(Request $request)
    {     $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'nom' => 'required|string',
        ]);

        $genre = Genre::create($request->all());
        return response()->json($genre, 201);
    }

    // Mettre à jour un genre
    public function update(Request $request, $id)
    {     $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'nom' => 'required|string',
        ]);

        $genre = Genre::findOrFail($id);
        $genre->update($request->all());
        return response()->json($genre, 200);
    }

    // Supprimer un genre
    public function destroy($id)
    {     $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        Genre::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}


