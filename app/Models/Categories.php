<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{
    use HasFactory;
    protected $table='categories' ;
    protected $fillable = ['nom'];

    // Relation avec les produits
    public function produits()
    {
        return $this->hasMany(Produits::class );
    }
    public function sousCategorie()
{
    return $this->hasMany(SousCategorie::class);
}
}
