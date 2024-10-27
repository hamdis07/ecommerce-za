<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
   // public function __construct()
   // {
        // dd('sdfs');
        // $this->middleware('auth:api', ['except' => ['login', 'registre', 'forgotPassword']]);
   // }

    //public function login(Request $request)
    //{
     //   $credentials = $request->only('email', 'password');

      //  if (! $token = auth()->attempt($credentials)) {
       //     return response()->json(['error' => 'Unauthorized'], 401);
//}

       // return $this->respondWithToken($token);
   // }


   public function login(Request $request)
   {
       $validator = Validator::make($request->all(), [
           'email' => 'required|email',
           'password' => 'required|string',
       ]);

       if ($validator->fails()) {
           return response()->json(['error' => $validator->errors()], 422);
       }

       $credentials = $request->only('email', 'password');
       if (!Auth::attempt($credentials)) {
           return response()->json(['error' => 'Unauthorized'], 401);
       }

       $token = Auth::attempt($credentials);

       return response()->json(['token' => $token], 200);
    }
    public function registre(Request $request)
{
    $rules = [
        'nom' => 'required|string|max:255',
        'prenom' => 'required|string|max:255',
        'genre' => 'required|string|in:Male,Female,Other',
        'date_de_naissance' => 'required|date',
        'Addresse' => 'required|string|max:255',
        'occupation' => 'required|string|max:255',
        'etat_social' => 'required|string|max:255',
        'numero_telephone' => 'required|string|max:255',
        'user_name' => 'required|string|max:255|unique:users',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
        'user_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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

        $user = new User();
        $user->nom = $validatedData['nom'];
        $user->prenom = $validatedData['prenom'];
        $user->genre = $validatedData['genre'];
        $user->date_de_naissance = $validatedData['date_de_naissance'];
        $user->Addresse = $validatedData['Addresse'];
        $user->occupation = $validatedData['occupation'];
        $user->etat_social = $validatedData['etat_social'];
        $user->numero_telephone = $validatedData['numero_telephone'];
        $user->user_name = $validatedData['user_name'];
        $user->email = $validatedData['email'];
        $user->email_verified_at = now();
        $user->user_image = $imageUrl;
        $user->password = Hash::make($validatedData['password']);
        $user->save();

        $role = Role::where('name', 'client')->first();
        if ($role) {
            $user->assignRole($role);
        }

        return response()->json([
            'message' => 'User Created',
            'user' => $user
        ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error('Validation error: ', $e->errors());

        return response()->json([
            'message' => 'Validation error',
            'errors' => $e->errors(),
        ], 422);
    }
}


    public function me()
    {
        return response()->json(auth()->user());
    }

    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }



    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)], 200)
            : response()->json(['error' => __($status)], 400);
    }

public function resetPassword(Request $request)
{
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|string|min:8|confirmed',
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->password = Hash::make($password);
            $user->save();
        }
    );

    if ($status === Password::PASSWORD_RESET) {
        return response()->json(['message' => __($status)], 200);
    } else {
        Log::error('Password reset failed', ['status' => $status]);
        return response()->json(['error' => __($status)], 400);
    }
}

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    public function consulterCoordonnees()
    {
        // Récupérer l'utilisateur authentifié
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        return response()->json([
            'message' => 'Informations utilisateur récupérées',
            'user' => $user
        ], 200);
    }
    // public function modifierCoordonnees(Request $request)
    // {
    //     // Récupérer l'utilisateur authentifié
    //     $user = auth()->user();

    //     if (!$user) {
    //         return response()->json([
    //             'message' => 'Utilisateur non trouvé',
    //         ], 404);
    //     }

    //     $rules = [
    //         'nom' => 'nullable|string|max:255',
    //         'prenom' => 'nullable|string|max:255',
    //         'genre' => 'nullable|string|in:Male,Female,Other,Homme,Femme,Autre',
    //         'date_de_naissance' => 'nullable|date',
    //         'Addresse' => 'nullable|string|max:255',
    //         'occupation' => 'nullable|string|max:255',
    //         'etat_social' => 'nullable|string|max:255',
    //         'numero_telephone' => 'nullable|string|max:255',
    //         'user_name' => 'nullable|string|max:255|unique:users,user_name,' . $user->id,
    //         'email' => 'nullable|string|email|max:255|unique:users,email,' . $user->id,
    //         'password' => 'nullable|string|min:8|confirmed',
    //         'current_password' => 'required|string', // Require current password
    //         'user_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    //     ];

    //     try {
    //         $validatedData = $request->validate($rules);

    //         // Verify the current password
    //         if (!Hash::check($validatedData['current_password'], $user->password)) {
    //             return response()->json([
    //                 'message' => 'Le mot de passe actuel est incorrect',
    //             ], 400);
    //         }

    //        // Vérifiez si l'utilisateur souhaite mettre à jour l'image
    //         if ($request->hasFile('user_image')) {
    //             // Supprimer l'ancienne image
    //             if ($user->user_image) {
    //                 $oldImagePath = public_path('images') . '/' . basename($user->user_image);
    //                 if (file_exists($oldImagePath)) {
    //                     unlink($oldImagePath);
    //                 }
    //             }

    //             // Enregistrer la nouvelle image
    //             $image = $request->file('user_image');
    //             $imageName = time() . '_' . $image->getClientOriginalName();
    //             $image->move(public_path('images'), $imageName);
    //             $user->user_image = asset('images/' . $imageName);
    //         }



    //         // Remove current_password from validated data
    //         unset($validatedData['current_password']);

    //         // Mettre à jour les informations utilisateur
    //         if (isset($validatedData['password'])) {
    //             $validatedData['password'] = Hash::make($validatedData['password']);
    //         }

    //         $user->update($validatedData);

    //         return response()->json([
    //             'message' => 'Coordonnées mises à jour avec succès',
    //             'user' => $user
    //         ], 200);
    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         // Log the validation errors
    //         \Log::error('Validation error: ', $e->errors());

    //         return response()->json([
    //             'message' => 'Erreur de validation',
    //             'errors' => $e->errors(),
    //         ], 422);
    //     }
    // }
    public function modifierCoordonnees(Request $request)
    {
        // Récupérer l'utilisateur authentifié
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Règles de validation
        $rules = [
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'genre' => 'sometimes|string|in:Male,Female,Other,femme,homme,autre',
            'date_de_naissance' => 'sometimes|date',
            'addresse' => 'sometimes|string|max:255',
            'occupation' => 'sometimes|string|max:255',
            'etat_social' => 'sometimes|string|max:255',
            'numero_telephone' => 'sometimes|string|max:255',
            'user_name' => 'sometimes|string|max:255|unique:users,user_name,' . $user->id,
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'current_password' => 'required_with:password|string|min:8',
            'password' => 'nullable|string|min:8|confirmed',
            'password_confirmation' => 'nullable|same:password',
            'user_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        try {
            // Valider les données
            $validatedData = $request->validate($rules);

            // Vérifier le mot de passe actuel
            if (!empty($validatedData['password']) && !Hash::check($validatedData['current_password'], $user->password)) {
                return response()->json(['message' => 'Le mot de passe actuel est incorrect.'], 403);
            }

            // Vérifiez si l'utilisateur souhaite mettre à jour l'image
            if ($request->hasFile('user_image')) {
                // Supprimer l'ancienne image
                if ($user->user_image) {
                    $oldImagePath = public_path('images') . '/' . basename($user->user_image);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                // Enregistrer la nouvelle image
                $image = $request->file('user_image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('images'), $imageName);
                $user->user_image = asset('images/' . $imageName);
            }

            // Mettre à jour les informations de l'utilisateur
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
            ]));

            // Mettre à jour le mot de passe si fourni
            if (!empty($validatedData['password'])) {
                $user->password = Hash::make($validatedData['password']);
            }

            // Enregistrer les modifications
            $user->save();

            return response()->json([
                'message' => 'Profil mis à jour avec succès.',
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



    public function historiquedachat()
    {
        // Récupérer l'utilisateur connecté
        $user = Auth::user();

        // Récupérer les commandes de l'utilisateur, triées par date de création (du plus récent au plus ancien)
        $commandes = $user->commandes()
            ->with('produits', 'paiement', 'livraisondetails')
            ->orderBy('created_at', 'desc') // Tri par date de création, du plus récent au plus ancien
            ->get();

        // Retourner la vue ou JSON
        return response()->json([
            'commandes' => $commandes
        ]);
    }
}
