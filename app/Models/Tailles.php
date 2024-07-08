<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tailles extends Model
{
    use HasFactory;
    protected $fillable = ['nom'];

    // Relation avec les produits
    public function produits()
    {
        return $this->belongsToMany(Produits::class, 'quantite')->withPivot('couleurs_id', 'quantite');
    }
}
