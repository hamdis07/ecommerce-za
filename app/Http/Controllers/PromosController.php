<?php



namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Promos;
use App\Models\Produits;
use Illuminate\Http\Request;

class PromosController extends Controller
{

    public function applyPromosToMultipleProducts(Request $request)
    {$user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $productIds = $request->input('product_ids', []); // IDs des produits à mettre à jour
        $promoData = $request->input('promo', []);

        if (count($productIds) > 0 && $this->isPromoDataValid($promoData)) {
            $promotion = Promos::firstOrCreate([
                'nom' => $promoData['nom'],
                'pourcentage_reduction' => $promoData['pourcentage_reduction'],
                'date_debut' => $promoData['date_debut'],
                'date_fin' => $promoData['date_fin']
            ]);

            foreach ($productIds as $idProduit) {
                $produit = Produits::findOrFail($idProduit);

                $produit->promo_id = $promotion->id;

                $currentDate = now();
                if ($currentDate->between($promotion->date_debut, $promotion->date_fin)) {
                    $produit->prix = $produit->prix_initial * (1 - $promotion->pourcentage_reduction / 100);
                } else {
                    $produit->prix = $produit->prix_initial;
                }

                $produit->save();
            }

            return response()->json('Promotion appliquée avec succès à plusieurs produits.', 200);
        } else {
            return response()->json('Données de promotion manquantes ou invalides.', 400);
        }
    }

    private function isPromoDataValid($promoData)
    {
        return isset($promoData['nom']) &&
               isset($promoData['pourcentage_reduction']) &&
               isset($promoData['date_debut']) &&
               isset($promoData['date_fin']) &&
               is_numeric($promoData['pourcentage_reduction']) &&
               $promoData['pourcentage_reduction'] > 0 && $promoData['pourcentage_reduction'] <= 100 && // Vérification du pourcentage
               strtotime($promoData['date_debut']) &&
               strtotime($promoData['date_fin']);
    }

    public function index()
    {
        $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Add pagination, specifying 10 items per page (you can adjust this value)
        $promos = Promos::paginate(10);

        return response()->json($promos);
    }


    // Afficher une seule promotion
    public function show($id)
    {   $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $promo = Promos::findOrFail($id);
        return response()->json($promo);
    }

    // Enregistrer une nouvelle promotion
    public function store(Request $request)
    {   $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
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
    {   $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'nom' => 'sometimes|string',
            'pourcentage_reduction' => 'sometimes|numeric',
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date|after:date_debut',
        ]);

        $promo = Promos::findOrFail($id);
        $promo->update($request->all());
        return response()->json($promo, 200);
    }

    // Supprimer une promotion
    public function destroy($id)
{
    $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Trouver la promotion à supprimer
    $promo = Promos::findOrFail($id);

    // Mettre à jour tous les produits qui utilisent cette promotion
    $produits = Produits::where('promo_id', $promo->id)->get();

    foreach ($produits as $produit) {
        // Réinitialiser le prix à son prix initial
        $produit->prix = $produit->prix_initial;
        $produit->promo_id = null;  // Supprimer l'association de la promotion
        $produit->save();
    }

    // Supprimer la promotion
    $promo->delete();

    return response()->json(['message' => 'Promotion supprimée et produits mis à jour.'], 200);
}public function applyExistingPromoToMultipleProducts(Request $request, $promo_id)
{
    $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    // Vérifier si l'utilisateur a les droits appropriés
    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $productIds = $request->input('product_ids', []); // IDs des produits à mettre à jour
    $promoId = $promo_id; // Récupérer l'ID de la promotion à partir du paramètre de la route

    // Vérifier que des produits ont été sélectionnés et qu'une promotion existe
    if (count($productIds) > 0 && $promoId) {
        // Rechercher la promotion existante
        $promotion = Promos::findOrFail($promoId);

        foreach ($productIds as $idProduit) {
            $produit = Produits::findOrFail($idProduit);

            // Appliquer la promotion au produit
            $produit->promo_id = $promotion->id;

            // Mettre à jour le prix selon la promotion si elle est active
            $currentDate = now();
            if ($currentDate->between($promotion->date_debut, $promotion->date_fin)) {
                $produit->prix = $produit->prix_initial * (1 - $promotion->pourcentage_reduction / 100);
            } else {
                $produit->prix = $produit->prix_initial;
            }

            $produit->save();
        }

        return response()->json('Promotion existante appliquée avec succès à plusieurs produits.', 200);
    } else {
        return response()->json('Données manquantes ou promotion inexistante.', 400);
    }
}


public function getProductsByPromoId($promoId)
{
    $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Trouver la promotion par son ID
    $promotion = Promos::findOrFail($promoId);

    // Récupérer tous les produits associés à cette promotion
    $produits = Produits::where('promo_id', $promoId)->get();

    // Retourner la réponse avec les produits
    return response()->json($produits, 200);
}
public function allproducts()
{ $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Récupère tous les produits
    $produits = Produits::all();

    // Retourne les produits en réponse JSON
    return response()->json($produits);
}

}


