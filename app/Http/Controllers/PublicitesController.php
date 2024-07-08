<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\publicites;
class PublicitesController extends Controller
{

    // Méthode pour créer une nouvelle bannière publicitaire
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nom' => 'required|string',

            'detail' => 'nullable|string',
            'date_lancement' => 'required|date',
            'date_fin' => 'required|date',
            'montant_paye' => 'required|numeric',
            'image' => 'nullable|file|image|max:2048', // Validation pour l'image
            'video' => 'nullable|file|mimes:mp4,mov,avi|max:20480', // Validation pour la vidéo
            'affiche' => 'nullable|file|image|max:2048' // Validation pour l'affiche
        ]);

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

        $banniere = Publicites::create($validatedData);
        return response()->json($banniere, 201);


    }


    public function index()
    {
        $bannieres = Publicites::all();
        return response()->json($bannieres);
    }
    public function show($id)
    {
        $banniere = Publicites::findOrFail($id);
        return response()->json($banniere);
    }
    public function destroy($id)
    {
        $banniere = Publicites::findOrFail($id);
        $banniere->delete();
        return response()->json('Bannière publicitaire supprimée avec succès', 200);
    }

 // Méthode pour mettre à jour une bannière publicitaire
public function update(Request $request, $id)
{
    $banniere = Publicites::findOrFail($id);

    $validatedData = $request->validate([
        'nom' => 'sometimes|string',

        'detail' => 'nullable|string',
        'date_lancement' => 'sometimes|date',
        'date_fin' => 'sometimes|date',
        'montant_paye' => 'sometimes|numeric',
        'image' => 'nullable|file|image|max:2048', // Validation pour l'image
        'video' => 'nullable|file|mimes:mp4,mov,avi|max:20480', // Validation pour la vidéo
        'affiche' => 'nullable|file|image|max:2048' // Validation pour l'affiche
    ]);
   // dd($validatedData);
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


    $banniere->update($validatedData); //dd($banniere);
    return response()->json($banniere, 200);
}

}


