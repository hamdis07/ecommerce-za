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
    public function __construct()
    {
        // dd('sdfs');
        // $this->middleware('auth:api', ['except' => ['login', 'registre', 'forgotPassword']]);
    }

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
        // 1. Définir les règles de validation
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
            'user_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Image facultative
        ];

        // 2. Valider les données de la requête
     try{
        $validatedData = $request->validate($rules);
        //dd($request->validated());
        // 3. Gérer le téléchargement de l'image si elle est présente
        $imageUrl = null;
        if ($request->hasFile('user_image')) {
            $image = $request->file('user_image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images'), $imageName);
            $imageUrl = asset('images/' . $imageName);
        }

        // 4. Créer l'utilisateur dans la base de données en respectant l'ordre des colonnes
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

        // 5. Assigner un rôle à l'utilisateur (exemple avec Spatie\Permission)
        $role = Role::where('name', 'client')->first();
        if ($role) {
            $user->assignRole($role);
        }

        // 6. Retourner une réponse JSON
        return response()->json([
            'message' => 'User Created',
            'user' => $user
        ], 201);
    }catch (\Illuminate\Validation\ValidationException $e) {
        // Gérer l'exception de validation ici
        return response()->json([
            'message' => 'Validation error',
            'errors' => $e->errors(),
        ], 422);
    }}

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

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }


}
