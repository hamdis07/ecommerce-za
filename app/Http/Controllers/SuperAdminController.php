<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class SuperAdminController extends Controller
{
    public function createadministrateur(Request $request)
    {
        $rules = [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'genre' => 'required|string|in:Male,Female,Other',
            'date_de_naissance' => 'required|date',
            'addresse' => 'required|string|max:255',
            'occupation' => 'required|string|max:255',
            'etat_social' => 'required|string|max:255',
            'numero_telephone' => 'required|string|max:255',
            'user_name' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'user_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        // Valider les données de la requête
        $validatedData = $request->validate($rules);

        // Traiter l'upload d'image si disponible
        $imageUrl = "";
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images'), $imageName);
            $imageUrl = asset('images/' . $imageName);
        }

        // Créer un nouvel utilisateur avec les données validées
        $user = User::create([
            'nom' => $validatedData['nom'],
            'prenom' => $validatedData['prenom'],
            'genre' => $validatedData['genre'],
            'date_de_naissance' => $validatedData['date_de_naissance'],
            'addresse' => $validatedData['addresse'],
            'occupation' => $validatedData['occupation'],
            'etat_social' => $validatedData['etat_social'],
            'numero_telephone' => $validatedData['numero_telephone'],
            'user_name' => $validatedData['user_name'],
            'email' => $validatedData['email'],
            'email_verified_at' => now(),
            'user_image' => $imageUrl,
            'password' => Hash::make($validatedData['password']),
        ]);

        // Assigner un rôle à l'utilisateur
        $role = Role::where('name', $request->role)->first();
        if ($role) {
            $user->assignRole([$role->id]);
        }

        return response()->json("Utilisateur créé avec succès");
    }

    public function updateadmin(Request $request, $id)
    {
        $rules = [
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'genre' => 'sometimes|string|in:Male,Female,Other',
            'date_de_naissance' => 'sometimes|date',
            'addresse' => 'sometimes|string|max:255',
            'occupation' => 'sometimes|string|max:255',
            'etat_social' => 'sometimes|string|max:255',
            'numero_telephone' => 'sometimes|string|max:255',
            'user_name' => 'sometimes|string|max:255|unique:users,user_name,' . $id,
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'user_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        // Valider les données de la requête
        $validatedData = $request->validate($rules);

        // Récupérer l'utilisateur par son ID
        $user = User::findOrFail($id);

        // Mettre à jour les données de l'utilisateur avec les données validées
        $user->update($validatedData);

        // Mettre à jour le mot de passe si fourni
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
            $user->save();
        }

        // Gérer l'upload d'image si disponible
        $imageUrl = $user->user_image;
        if ($request->hasFile('user_image')) {
            $image = $request->file('user_image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images'), $imageName);
            $imageUrl = asset('images/' . $imageName);
        }

        // Mettre à jour le rôle de l'utilisateur
        if ($request->has('role')) {
            $role = Role::where('name', $request->role)->first();
            if ($role) {
                $user->syncRoles([$role->id]);
            }
        }

        // Mettre à jour l'URL de l'image de l'utilisateur
        $user->user_image = $imageUrl;
        $user->save();

        return response()->json("Utilisateur mis à jour avec succès");
    }

public function deleteUser($id)
{
    $user = User::find($id);
    if (!$user) {
        return response()->json(['error' => 'Utilisateur non trouvé.'], 404);
    }
    $user->delete();
    return response()->json(['message' => 'Utilisateur supprimé avec succès.']);
}

public function showadmin($id)
{
    $user = User::find($id);
    if (!$user) {
        return response()->json(['error' => 'Utilisateur non trouvé.'], 404);
    }
    return response()->json(['user' => $user]);
}

public function searchByUsernameadmin(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_name' => 'required|string|max:255',
    ]);
    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()->first()], 400);
    }
    $user = User::where('user_name', $request->user_name)->first();
    if (!$user) {
        return response()->json(['error' => 'Utilisateur non trouvé.'], 404);
    }
    return response()->json(['user' => $user]);
}
public function rechercheradmin(Request $request)
{
    $query = User::query();

    // Filtre par statut
   // if ($request->has('statut')) {
   //     $query->where('statut', $request->statut);
   // }

    // Filtre par nom
    if ($request->has('nom')) {
        $query->where('nom', 'like', '%' . $request->nom . '%');
    }

    // Filtre par prénom
    if ($request->has('prenom')) {
        $query->where('prenom', 'like', '%' . $request->prenom . '%');
    }

    // Filtre par numéro de téléphone
    if ($request->has('numero_telephone')) {
        $query->where('numero_telephone', 'like', '%' . $request->numero_telephone . '%');
    }

    // Exécuter la requête
    $utilisateurs = $query->get();

    return response()->json($utilisateurs);
}
public function getUsersByRole(Request $request)
{
    // Vérifiez si un rôle est passé dans la requête
    $validator = Validator::make($request->all(), [
        'role' => 'required|string|in:operateur,admin,dispatcheur,super admin,digital marketing',
    ]);

    // Si la validation échoue, renvoyer une réponse d'erreur
    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()->first()], 400);
    }

    // Récupérer l'ID du rôle
    $role = Role::where('name', $request->role)->first();

    // Si le rôle n'existe pas, renvoyer une réponse d'erreur
    if (!$role) {
        return response()->json(['error' => 'Rôle non trouvé.'], 404);
    }

    // Récupérer les utilisateurs ayant le rôle spécifié
    $users = $role->users;

    // Retourner la liste des utilisateurs
    return response()->json(['users' => $users]);
}
 // Assurez-vous d'importer le modèle User si ce n'est pas déjà fait

public function getAdmins()
{
    // Récupérer tous les utilisateurs
    $users = User::all();

    return response()->json(['users' => $users]);
}

}

