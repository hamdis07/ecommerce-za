<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Publicites;
use Illuminate\Support\Facades\Validator;

class PublicitesController extends Controller
{
    // Méthode pour créer une nouvelle bannière publicitaire
    public function store(Request $request)
    {
        $rules = [
            'nom' => 'required|string',
            'detail' => 'nullable|string',
            'date_lancement' => 'required|date',
            'date_fin' => 'required|date|after:date_lancement',
            'montant_paye' => 'required|numeric',
            'image' => 'nullable|file|image|max:2048', // Validation pour l'image
            'video' => 'nullable|file|mimes:mp4,mov,avi|max:20480', // Validation pour la vidéo
            'affiche' => 'nullable|file|image|max:2048' // Validation pour l'affiche
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['error' => 'Erreur de validation', 'messages' => $validator->errors()], 422);
        }

        // Affiche les données reçues
        //dd($request->all());

        $validatedData = $validator->validated();

        $imageUrl = '';
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images'), $imageName);
            $imageUrl = asset('images/' . $imageName);
        }

        $videoUrl = '';
        if ($request->hasFile('video')) {
            $video = $request->file('video');
            $videoName = time() . '_' . $video->getClientOriginalName();
            $video->move(public_path('videos'), $videoName);
            $videoUrl = asset('videos/' . $videoName);
        }

        $afficheUrl = '';
        if ($request->hasFile('affiche')) {
            $affiche = $request->file('affiche');
            $afficheName = time() . '_' . $affiche->getClientOriginalName();
            $affiche->move(public_path('affiches'), $afficheName);
            $afficheUrl = asset('affiches/' . $afficheName);
        }

        $validatedData['image'] = $imageUrl;
        $validatedData['video'] = $videoUrl;
        $validatedData['affiche'] = $afficheUrl;

        try {
            $banniere = Publicites::create($validatedData);
            return response()->json($banniere, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la création de la bannière publicitaire', 'message' => $e->getMessage()], 500);
        }
    }

    public function index()
    {
        try {
            $bannieres = Publicites::all();
            return response()->json($bannieres);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la récupération des bannières publicitaires', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $banniere = Publicites::findOrFail($id);
            return response()->json($banniere);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Bannière publicitaire non trouvée', 'message' => $e->getMessage()], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $banniere = Publicites::findOrFail($id);
            $banniere->delete();
            return response()->json('Bannière publicitaire supprimée avec succès', 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la suppression de la bannière publicitaire', 'message' => $e->getMessage()], 500);
        }
    }

    // Méthode pour mettre à jour une bannière publicitaire
    public function update(Request $request, $id)
    {
        try {
            $banniere = Publicites::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Bannière publicitaire non trouvée', 'message' => $e->getMessage()], 404);
        }

        try {
            $validatedData = $request->validate([
                'nom' => 'sometimes|string',
                'detail' => 'nullable|string',
                'date_lancement' => 'sometimes|date',
                'date_fin' => 'sometimes|date|after:date_lancement',
                'montant_paye' => 'sometimes|numeric',
                'image' => 'nullable|file|image|max:2048', // Validation pour l'image
                'video' => 'nullable|file|mimes:mp4,mov,avi|max:20480', // Validation pour la vidéo
                'affiche' => 'nullable|file|image|max:2048' // Validation pour l'affiche
            ]);
          //  dd($validatedData);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Erreur de validation', 'messages' => $e->errors()], 422);
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images'), $imageName);
            $imageUrl = asset('images/' . $imageName);
            $validatedData['image'] = $imageUrl;
        }

        if ($request->hasFile('video')) {
            $video = $request->file('video');
            $videoName = time() . '_' . $video->getClientOriginalName();
            $video->move(public_path('videos'), $videoName);
            $videoUrl = asset('videos/' . $videoName);
            $validatedData['video'] = $videoUrl;
        }

        if ($request->hasFile('affiche')) {
            $affiche = $request->file('affiche');
            $afficheName = time() . '_' . $affiche->getClientOriginalName();
            $affiche->move(public_path('affiches'), $afficheName);
            $afficheUrl = asset('affiches/' . $afficheName);
            $validatedData['affiche'] = $afficheUrl;
        }

       // dd($validatedData);

        try {
            $banniere->update($validatedData);
            return response()->json($banniere, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors de la mise à jour de la bannière publicitaire', 'message' => $e->getMessage()], 500);
        }
    }
}
