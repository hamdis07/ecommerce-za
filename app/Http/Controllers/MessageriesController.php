<?php
namespace App\Http\Controllers;

use App\Models\Messagerie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

//class MessageriesController extends Controller
// {
//     public function store(Request $request)
//     {


//         if (!Auth::check()) {
//             return response()->json(['error' => 'Unauthorized'], 401);
//         }

//         // Récupérer l'utilisateur actuellement authentifié
//         $user = Auth::user();
//         $userId = $user->id;

//         $request->validate([
//             'objet' => 'required|string',
//             'nom' => 'required|string',
//             'prenom' => 'required|string',
//             'telephone' => 'required|string',
//             'email' => 'required|email',
//             'sujet' => 'required|string',
//             'description' => 'required|string',
//         ]);

//         $message = Messageries::create([
//             'user_id' => auth()->id(),
//             'objet' => $request->objet,
//             'nom' => $request->nom,
//             'prenom' => $request->prenom,
//             'telephone' => $request->telephone,
//             'email' => $request->email,
//             'sujet' => $request->sujet,
//             'description' => $request->description,
//         ]);

//         // Vous pouvez envoyer une notification à l'administrateur ici
//         // Par exemple : Mail::to($adminEmail)->send(new NewMessageNotification($message));

//         return response()->json($message, 201);
//     }

    // Autres fonctions pour la gestion des messages
