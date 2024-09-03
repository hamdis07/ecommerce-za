<?php



// namespace App\Http\Controllers;
//use Illuminate\Support\Facades\Auth;

// use App\Models\Promos;
// use Illuminate\Http\Request;

// class PromosController extends Controller
// {
//     public function __construct()
//     {
//         $this->middleware('role:admin|superadmin|dispatcheur|operateur|responsable_marketing')->only([
//             ' store',
//             'update',
//             'destroy',
//              'index',
//              'show',
//         ]);
//     }    public function index()
//     {   $user = Auth::user();
//         $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

//         if (!$user || !$user->hasAnyRole($roles)) {
//             return response()->json(['message' => 'Unauthorized'], 403);
//         }
//         $promos = Promos::all();
//         return response()->json($promos);
//     }

//     // Afficher une seule promotion
//     public function show($id)
//     {   $user = Auth::user();
//         $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

//         if (!$user || !$user->hasAnyRole($roles)) {
//             return response()->json(['message' => 'Unauthorized'], 403);
//         }
//         $promo = Promos::findOrFail($id);
//         return response()->json($promo);
//     }

//     // Enregistrer une nouvelle promotion
//     public function store(Request $request)
//     {   $user = Auth::user();
//         $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

//         if (!$user || !$user->hasAnyRole($roles)) {
//             return response()->json(['message' => 'Unauthorized'], 403);
//         }
//         $request->validate([
//             'nom' => 'required|string',
//             'pourcentage_reduction' => 'required|numeric',
//             'date_debut' => 'required|date',
//             'date_fin' => 'required|date|after:date_debut',
//         ]);

//         $promo = Promos::create($request->all());
//         return response()->json($promo, 201);
//     }

//     // Mettre à jour une promotion
//     public function update(Request $request, $id)
//     {   $user = Auth::user();
//         $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

//         if (!$user || !$user->hasAnyRole($roles)) {
//             return response()->json(['message' => 'Unauthorized'], 403);
//         }
//         $request->validate([
//             'nom' => 'sometimes|string',
//             'pourcentage_reduction' => 'sometimes|numeric',
//             'date_debut' => 'sometimes|date',
//             'date_fin' => 'sometimes|date|after:date_debut',
//         ]);

//         $promo = Promos::findOrFail($id);
//         $promo->update($request->all());
//         return response()->json($promo, 200);
//     }

//     // Supprimer une promotion
//     public function destroy($id)
//     {   $user = Auth::user();
//         $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

//         if (!$user || !$user->hasAnyRole($roles)) {
//             return response()->json(['message' => 'Unauthorized'], 403);
//         }
//         Promos::findOrFail($id)->delete();
//         return response()->json(null, 204);
//     }
//}


