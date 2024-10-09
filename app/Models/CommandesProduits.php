<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommandesProduits extends Model
{
    use HasFactory;

    protected $table = 'commandesproduits';

    protected $fillable = ['commandes_id', 'produits_id', 'quantite', 'taille', 'couleur', 'prix_total'];

    public function commande()
    {
        return $this->belongsTo(Commandes::class);
    }

    public function produit()
    {
        return $this->belongsTo(Produits::class);
    }
}
