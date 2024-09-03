<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Images extends Model
{
    use HasFactory;
    protected $table = 'images';
    protected $fillable = ['chemin_image','produit_id'];

    public function produit()
    {
        return $this->belongsTo(Produits::class,'produit_id');
    }
}
