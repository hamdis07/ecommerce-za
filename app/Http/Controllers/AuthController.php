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
       // 1. Valider les données de la requête
       $validator = Validator::make($request->all(), [
           'email' => 'required|email',
           'password' => 'required|string',
       ]);

       if ($validator->fails()) {
           return response()->json(['error' => $validator->errors()], 422);
       }

       // 2. Tentative de connexion
       $credentials = $request->only('email', 'password');
       if (!Auth::attempt($credentials)) {
           return response()->json(['error' => 'Unauthorized'], 401);
       }

       // 3. Générer le token JWT
       $token = Auth::attempt($credentials);

       // 4. Réponse JSON avec le token JWT
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

        // Handle the image upload if present
        $imageUrl = null;
        if ($request->hasFile('user_image')) {
            $image = $request->file('user_image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images'), $imageName);
            $imageUrl = asset('images/' . $imageName);
        }

        // Create user
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

        // Assign role
        $role = Role::where('name', 'client')->first();
        if ($role) {
            $user->assignRole($role);
        }

        return response()->json([
            'message' => 'User Created',
            'user' => $user
        ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        // Log the validation errors
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

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)], 200)
            : response()->json(['error' => __($status)], 400);
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
    public function modifierCoordonnees(Request $request)
    {
        // Récupérer l'utilisateur authentifié
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        $rules = [
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'genre' => 'sometimes|string|in:Male,Female,Other',
            'date_de_naissance' => 'sometimes|date',
            'Addresse' => 'sometimes|string|max:255',
            'occupation' => 'sometimes|string|max:255',
            'etat_social' => 'sometimes|string|max:255',
            'numero_telephone' => 'sometimes|string|max:255',
            'user_name' => 'sometimes|string|max:255|unique:users,user_name,' . $user->id,
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8|confirmed',
            'current_password' => 'required|string', // Require current password
            'user_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        try {
            $validatedData = $request->validate($rules);

            // Verify the current password
            if (!Hash::check($validatedData['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'Le mot de passe actuel est incorrect',
                ], 400);
            }

            // Handle the image upload if present
            if ($request->hasFile('user_image')) {
                $image = $request->file('user_image');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('images'), $imageName);
                $user->user_image = asset('images/' . $imageName);
            }

            // Remove current_password from validated data
            unset($validatedData['current_password']);

            // Mettre à jour les informations utilisateur
            if (isset($validatedData['password'])) {
                $validatedData['password'] = Hash::make($validatedData['password']);
            }

            $user->update($validatedData);

            return response()->json([
                'message' => 'Coordonnées mises à jour avec succès',
                'user' => $user
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log the validation errors
            \Log::error('Validation error: ', $e->errors());

            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function historiquedachat()
    {
        // Récupérer l'utilisateur connecté
        $user = Auth::user();

        // Récupérer les commandes de l'utilisateur
        $commandes = $user->commandes()->with('produits', 'paiement', 'livraisondetails')->get();

        // Retourner la vue ou JSON
        return response()->json([
            'commandes' => $commandes
        ]);
    }
}
