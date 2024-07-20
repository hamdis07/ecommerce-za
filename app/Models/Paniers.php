<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paniers extends Model
{
    use HasFactory;
    protected $table='paniers';
    protected $fillable = ['panier_id','user_id','produit_id','quantite','taille','couleur','prix_total'];

    public function user()
    {
        return $this->belongsTo(Users::class);
    }

    public function produits()
    {
        return $this->belongsToMany(Produits::class, 'paniersproduits','panier_id', 'produit_id')


                    ->withPivot('quantite', 'taille', 'couleur','prix_total');
    }
    public function Paniersproduits()
    {
        return $this->hasMany(paniersproduits::class);
    }
    public function prix_total()
    {
        // Vérifiez si le produit est associé à l'élément du panier
        if ($this->produit) {
            // Calculez le prix total en multipliant la quantité par le prix du produit
            return $this->quantite * $this->produit->prix;
        }
    }
    public function commandes()
    {
        return $this->belongsTo(Commandes::class); // Un panneau appartient à une commande
    }
}
