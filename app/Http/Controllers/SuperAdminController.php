<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class SuperAdminController extends Controller
{
    public function createadministrateur(Request $request)
    {    $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $rules = [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'genre' => 'required|string|in:Male,Female,Other,femme,homme,autre',
            'date_de_naissance' => 'required|date',
            'addresse' => 'required|string|max:255',
            'occupation' => 'required|string|max:255',
            'etat_social' => 'required|string|max:255',
            'numero_telephone' => 'required|string|max:255',
            'user_name' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'user_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'role' => 'required|string|in:admin,superadmin,client,operateur,dispatcheur,responsable_marketing'
        ];

        try {
            $validatedData = $request->validate($rules);

            $imageUrl = null;
            if ($request->hasFile('user_image')) {
                $image = $request->file('user_image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('images'), $imageName);
                $imageUrl = asset('images/' . $imageName);
            }

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

            $role = $validatedData['role'];
            $user->assignRole($role);

            return response()->json([
                'message' => 'User created successfully.',
                'data' => $user
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erreur interne du serveur',
                'errors' => $e->errors()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur interne du serveur',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
    public function updateadmin(Request $request, $id)
{   $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $rules = [
        'nom' => 'nullable|string|max:255',
        'prenom' => 'nullable|string|max:255',
        'genre' => 'nullable|string|in:Male,Female,Other,femme,homme,autre',
        'date_de_naissance' => 'nullable|date',
        'addresse' => 'nullable|string|max:255',
        'occupation' => 'nullable|string|max:255',
        'etat_social' => 'nullable|string|max:255',
        'numero_telephone' => 'nullable|string|max:255',
        'user_name' => 'nullable|string|max:255|unique:users,user_name,' . $id,
        'email' => 'nullable|string|email|max:255|unique:users,email,' . $id,
        'password' => 'nullable|string|min:8',
        'user_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'role' => 'nullable|string|in:admin,superadmin,client,operateur,dispatcheur,responsable_marketing',
        'status' => 'nullable|string|in:actif,non actif,en attente,bloqué',
    ];

    try {
        $validatedData = $request->validate($rules);

        $user = User::findOrFail($id);

        if ($request->hasFile('user_image')) {
            if ($user->user_image) {
                $oldImagePath = public_path('images') . '/' . basename($user->user_image);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $image = $request->file('user_image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images'), $imageName);
            $user->user_image = asset('images/' . $imageName);
        }

        $user->update(array_filter([
            'nom' => $validatedData['nom'] ?? $user->nom,
            'prenom' => $validatedData['prenom'] ?? $user->prenom,
            'genre' => $validatedData['genre'] ?? $user->genre,
            'date_de_naissance' => $validatedData['date_de_naissance'] ?? $user->date_de_naissance,
            'addresse' => $validatedData['addresse'] ?? $user->addresse,
            'occupation' => $validatedData['occupation'] ?? $user->occupation,
            'etat_social' => $validatedData['etat_social'] ?? $user->etat_social,
            'numero_telephone' => $validatedData['numero_telephone'] ?? $user->numero_telephone,
            'user_name' => $validatedData['user_name'] ?? $user->user_name,
            'email' => $validatedData['email'] ?? $user->email,
            'status' => $validatedData['status'] ?? $user->status,
              ]));

        if (!empty($validatedData['password'])) {
            $user->password = Hash::make($validatedData['password']);
        }


        if (!empty($validatedData['role'])) {
            $user->syncRoles([$validatedData['role']]);
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => $user
        ], 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validation error',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Erreur interne du serveur',
            'errors' => $e->getMessage()
        ], 500);
    }
}


public function deleteUser($id)
{   $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $user = User::find($id);

    if (!$user) {
        return response()->json(['error' => 'Utilisateur non trouvé.'], 404);
    }

    $user->commandes()->update(['paiement_id' => null]);

    $user->delete();

    return response()->json(['message' => 'Utilisateur supprimé avec succès.']);
}

public function showadmin($id)
{
    $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    // Check if the authenticated user has any of the specified roles
    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Find the user by ID
    $user = User::find($id);
    if (!$user) {
        return response()->json(['error' => 'Utilisateur non trouvé.'], 404);
    }

    // Get the roles of the user
    $userRoles = $user->roles->pluck('name'); // Assuming the roles are related and you want the names

    // Include the roles in the response
    return response()->json([
        'user' => $user,
        'roles' => $userRoles
    ]);
}


public function searchByUsernameadmin(Request $request)
{   $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
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
{   $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $query = User::query();

    // Filtre par statut
   // if ($request->has('statut')) {
   //     $query->where('statut', $request->statut);
   // }

    if ($request->has('nom')) {
        $query->where('nom', 'like', '%' . $request->nom . '%');
    }

    if ($request->has('prenom')) {
        $query->where('prenom', 'like', '%' . $request->prenom . '%');
    }

    if ($request->has('numero_telephone')) {
        $query->where('numero_telephone', 'like', '%' . $request->numero_telephone . '%');
    }
    if ($request->has('user_name')) {
        $query->where('user_name', 'like', '%' . $request->user_name . '%');
    }

    $utilisateurs = $query->get();

    return response()->json($utilisateurs);
}
public function getUsersByRole(Request $request)
{   $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    // Vérifiez si un rôle est passé dans la requête
    $validator = Validator::make($request->all(), [
        'role' => 'required|string|in:superadmin,operateur,admin,dispatcheur,super admin,digital marketing',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()->first()], 400);
    }

    $role = Role::where('name', $request->role)->first();

    if (!$role) {
        return response()->json(['error' => 'Rôle non trouvé.'], 404);
    }

    $users = $role->users;


    return response()->json(['users' => $users]);
}

 public function getAdmins(Request $request)
 {   $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
     $perPage = $request->input('per_page', 10); // Number of items per page
     $sortBy = $request->input('sort_by', 'nom'); // Field to sort by (default: 'name')
     $sortOrder = $request->input('sort_order', 'asc'); // Sorting order (default: 'asc')

     // Validate sorting inputs
     if (!in_array($sortBy, ['nom', 'email', 'numero_telephone', 'occupation'])) {
         return response()->json([
             'message' => 'Invalid sort field',
         ], 400);
     }

     if (!in_array($sortOrder, ['asc', 'desc'])) {
         return response()->json([
             'message' => 'Invalid sort order',
         ], 400);
     }

     $clientRole = Role::where('name', 'client')->first();

     if (!$clientRole) {
         return response()->json([
             'message' => 'Client role not found',
         ], 404);
     }

     $admins = User::whereDoesntHave('roles', function($query) use ($clientRole) {
         $query->where('role_id', $clientRole->id);
     })
     ->orderBy($sortBy, $sortOrder)
     ->paginate($perPage);

     return response()->json([
         'data' => $admins->items(),
         'current_page' => $admins->currentPage(),
         'total_pages' => $admins->lastPage(),
         'total_items' => $admins->total()
     ]);
 }

 public function updateAdminStatus(Request $request, $id)
{   $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $rules = [
        'status' => 'required|string|in:actif,non actif,en attente,bloqué',
    ];

    try {
        $validatedData = $request->validate($rules);


        $user = User::findOrFail($id);

        $user->status = $validatedData['status'];
        $user->save();

        return response()->json([
            'message' => 'Statut mis à jour avec succès.',
            'data' => $user
        ], 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Erreur de validation',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Erreur interne du serveur',
            'errors' => $e->getMessage()
        ], 500);
    }
}


//////////////////////////////////////////////////////////////////////////////
 public function getClients(Request $request)
{   $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $perPage = $request->input('per_page', 10); // Number of items per page


    $clientRole = Role::where('name', 'client')->first();

    if (!$clientRole) {
        return response()->json([
            'message' => 'Client role not found',
        ], 404);
    }

    $clients = User::whereHas('roles', function($query) use ($clientRole) {
        $query->where('role_id', $clientRole->id);
    })->paginate($perPage);

    return response()->json([
        'data' => $clients->items(),
        'current_page' => $clients->currentPage(),
        'total_pages' => $clients->lastPage(),
        'total_items' => $clients->total()
    ]);
}


public function createclient(Request $request)
{   $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $rules = [
        'nom' => 'required|string|max:255',
        'prenom' => 'required|string|max:255',
        'genre' => 'required|string|in:Male,Female,Other,femme,homme,autre',
        'date_de_naissance' => 'required|date',
        'addresse' => 'required|string|max:255',
        'occupation' => 'required|string|max:255',
        'etat_social' => 'required|string|max:255',
        'numero_telephone' => 'required|string|max:255',
        'user_name' => 'required|string|max:255|unique:users',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
        'user_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'role' => 'required|string|in:client'
    ];

    try {
        $validatedData = $request->validate($rules);

        $imageUrl = null;
        if ($request->hasFile('user_image')) {
            $image = $request->file('user_image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images'), $imageName);
            $imageUrl = asset('images/' . $imageName);
        }

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

        $role = $validatedData['role'];
        $user->assignRole($role);

        return response()->json([
            'message' => 'Client créé avec succès.',
            'data' => $user
        ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Erreur de validation',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Erreur interne du serveur',
            'errors' => $e->getMessage()
        ], 500);
    }
}



public function updateclient(Request $request, $id)
{   $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $rules = [
        'nom' => 'sometimes|string|max:255',
        'prenom' => 'sometimes|string|max:255',
        'genre' => 'sometimes|string|in:Male,Female,Other,femme,homme,autre',
        'date_de_naissance' => 'sometimes|date',
        'addresse' => 'sometimes|string|max:255',
        'occupation' => 'sometimes|string|max:255',
        'etat_social' => 'sometimes|string|max:255',
        'numero_telephone' => 'sometimes|string|max:255',
        'user_name' => 'sometimes|string|max:255|unique:users,user_name,' . $id,
        'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
        'password' => 'nullable|string|min:8',
        'user_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'role' => 'sometimes|string|in:admin,superadmin,client,operateur,dispatcheur,responsable_marketing',
        'status' => 'sometimes|string|in:actif,non actif,en attente,bloqué',
    ];

    try {
        $validatedData = $request->validate($rules);

        $user = User::findOrFail($id);

        if ($request->hasFile('user_image')) {
            if ($user->user_image) {
                $oldImagePath = public_path('images') . '/' . basename($user->user_image);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }


            $image = $request->file('user_image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images'), $imageName);
            $user->user_image = asset('images/' . $imageName);
        }


        $user->fill(array_filter([
            'nom' => $validatedData['nom'] ?? $user->nom,
            'prenom' => $validatedData['prenom'] ?? $user->prenom,
            'genre' => $validatedData['genre'] ?? $user->genre,
            'date_de_naissance' => $validatedData['date_de_naissance'] ?? $user->date_de_naissance,
            'addresse' => $validatedData['addresse'] ?? $user->addresse,
            'occupation' => $validatedData['occupation'] ?? $user->occupation,
            'etat_social' => $validatedData['etat_social'] ?? $user->etat_social,
            'numero_telephone' => $validatedData['numero_telephone'] ?? $user->numero_telephone,
            'user_name' => $validatedData['user_name'] ?? $user->user_name,
            'email' => $validatedData['email'] ?? $user->email,
            'status' => $validatedData['status'] ?? $user->status,

        ]));

        if (!empty($validatedData['password'])) {
            $user->password = Hash::make($validatedData['password']);
        }

        if (!empty($validatedData['role'])) {

            $user->syncRoles([$validatedData['role']]);
        }

        $user->save();

        return response()->json([
            'message' => 'Client mis à jour avec succès.',
            'data' => $user
        ], 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Erreur de validation',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Erreur interne du serveur',
            'errors' => $e->getMessage()
        ], 500);
    }
}

public function deleteClient($id)
{   $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $user = User::find($id);

    if (!$user) {
        return response()->json(['error' => 'Client non trouvé.'], 404);
    }

    $user->commandes()->update(['paiement_id' => null]);

    $user->delete();

    return response()->json(['message' => 'Client supprimé avec succès.']);
}
public function showclient($id)
{   $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $user = User::find($id);
    if (!$user) {
        return response()->json(['error' => 'Client non trouvé.'], 404);
    }
    return response()->json(['user' => $user]);
}
public function searchByUsernameclient(Request $request)
{   $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $validator = Validator::make($request->all(), [
        'user_name' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()->first()], 400);
    }

    $user = User::where('user_name', $request->user_name)->first();

    if (!$user) {
        return response()->json(['error' => 'Client non trouvé.'], 404);
    }

    return response()->json(['user' => $user]);
}

public function rechercherclient(Request $request)
{
    $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    // Check if the user is authorized
    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Initialize the query
    $query = User::query();

    // Apply filters if provided
    if ($request->filled('nom')) {
        $query->where('nom', 'like', '%' . $request->input('nom') . '%');
    }
    if ($request->filled('user_name')) {
        $query->where('user_name', 'like', '%' . $request->input('user_name') . '%');
    }
    if ($request->filled('prenom')) {
        $query->where('prenom', 'like', '%' . $request->input('prenom') . '%');
    }
    if ($request->filled('numero_telephone')) {
        $query->where('numero_telephone', 'like', '%' . $request->input('numero_telephone') . '%');
    }

    // Execute the query and get results
    $clients = $query->get();

    // Return results as JSON
    return response()->json($clients);
}

public function getUsers(Request $request)
{ $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    // Optionally, you can filter users by role using the `role` query parameter
    $role = $request->query('role');

    if ($role) {
        // Use Spatie's `role` method to filter users by role
        $users = User::role($role)->get();
    } else {
        // Get all users if no role is specified
        $users = User::all();
    }

    return response()->json($users);
}


public function updateClientStatus(Request $request, $id)
{   $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $rules = [
        'status' => 'required|string|in:actif,non actif,en attente,bloqué',
    ];

    try {

        $validatedData = $request->validate($rules);

        $user = User::findOrFail($id);

        $user->status = $validatedData['status'];
        $user->save();

        return response()->json([
            'message' => 'Statut mis à jour avec succès.',
            'data' => $user
        ], 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Erreur de validation',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Erreur interne du serveur',
            'errors' => $e->getMessage()
        ], 500);
    }
}
















}




