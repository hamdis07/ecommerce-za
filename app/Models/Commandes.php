<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Commandes extends Model
{
    use HasFactory;

    protected $table='commandesss';
    protected $fillable = [
        'user_id', 'panier_id','monatant_total', 'statut','paiement_id','methode_paiement'
    ];

    public function user()
    {
        return $this->belongsTo(Users::class);
    }

    public function paniers()
    {
        return $this->hasMany(Paniers::class); // Une commande peut avoir plusieurs panneaux
    }

    public function livraisondetails()
    {
        return $this->hasOne(Livraisondetails::class);
    }
    public function paiement()
    {
        return $this->hasOne(Paiements::class);
    }

    public function calculerMontantTotal()
    {
        $montantTotal = 0;

        foreach ($this->panneaux->elements as $element) {
            $montantTotal += $element->prix_total();
        }

        return $montantTotal;
    }
    public function produits()
    {
        return $this->belongsToMany(Produits::class, 'commandesproduits')
                    ->withPivot('quantite', 'taille', 'couleur', 'prix_total');
    }

    // Autres méthodes et relations au besoin
}
