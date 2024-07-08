<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Magasins extends Model
{
    use HasFactory;
    protected $table='magasins';
    protected $fillable = ['nom', 'adresse','ville','code_postal','responsable','telephone'];

    public function produits()
    {
        return $this->belongsToMany(Produits::class,'quantite', 'magasin_id', 'produits_id')->withPivot('taille', 'couleur','quantite');
    }

}
