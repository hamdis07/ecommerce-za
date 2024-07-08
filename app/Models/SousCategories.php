<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SousCategories extends Model
{
    use HasFactory;
    protected $table ='Souscategories';


    protected $fillable = ['categorie_id', 'nom'];


    public function categories()
{
    return $this->belongsTo(Categories::class);
}

public function produits()
{
    return $this->hasMany(Produits::class);
}

}
