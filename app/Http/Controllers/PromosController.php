<?php



namespace App\Http\Controllers;

use App\Models\Promos;
use Illuminate\Http\Request;

class PromosController extends Controller
{
    // Afficher toutes les promotions
    public function index()
    {
        $promos = Promos::all();
        return response()->json($promos);
    }

    // Afficher une seule promotion
    public function show($id)
    {
        $promo = Promos::findOrFail($id);
        return response()->json($promo);
    }

    // Enregistrer une nouvelle promotion
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string',
            'pourcentage_reduction' => 'required|numeric',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
        ]);

        $promo = Promos::create($request->all());
        return response()->json($promo, 201);
    }

    // Mettre à jour une promotion
    public function update(Request $request, $id)
    {
        $request->validate([
            'nom' => 'required|string',
            'pourcentage_reduction' => 'required|numeric',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
        ]);

        $promo = Promos::findOrFail($id);
        $promo->update($request->all());
        return response()->json($promo, 200);
    }

    // Supprimer une promotion
    public function destroy($id)
    {
        Promos::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}


