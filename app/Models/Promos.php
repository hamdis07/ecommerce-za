<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promos extends Model
{
    protected $fillable = [
        'nom', 'pourcentage_reduction', 'date_debut', 'date_fin'
    ];
    protected $table='promos';
    /**
     * Les produits associés à la promotion.
     */
    public function produits()
    {
        return $this->hasMany(Produits::class);
    }
}
