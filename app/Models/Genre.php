<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    use HasFactory;
    protected $table='Genre';
    protected $fillable = ['nom'];

    // Relation avec les produits
    public function produits()
    {
        return $this->hasMany(Produits::class);
    }
}
