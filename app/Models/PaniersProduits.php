<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PaniersProduits extends Pivot
{
    protected $table = 'paniersproduits'; // Nom de la table pivot

    protected $fillable = ['panier_id', 'produit_id',  'quantite', 'couleur', 'taille','prix_total'];

    // Méthode pour calculer le prix total de chaque produit dans le panier
    public function panier()
    {
        return $this->belongsTo(Paniers::class);
    }

    public function produit()
    {
        return $this->belongsTo(Produits::class);
    }

    public function prix_total()
    {
        // Vérifiez si le produit est associé à l'élément du panier
        if ($this->produit) {
            // Calculez le prix total en multipliant la quantité par le prix du produit
            return $this->quantite * $this->produit->prix;
        }

        // Si le produit n'est pas disponible, retournez 0
        return 0;
    }

    public function user()
    {
        return $this->belongsTo(Users::class);
    }
}
