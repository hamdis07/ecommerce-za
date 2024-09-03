<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produits extends Model
{
    use HasFactory;
    protected $table='Produits';
    protected $fillable = ['references','nom_produit', 'image_url', 'description', 'prix','prix_initial','composition',  'entretien','mots_cles','is_featured', 'is_hidden'];

    public function categories()
    {
        return $this->belongsTo(Categories::class,'categorie_id');
    }
    public function tailles()
    {
        return $this->belongsToMany(Tailles::class, 'quantite')->withPivot('couleurs_id', 'quantite');
    }

    public function couleurs()
    {
        return $this->belongsToMany(Couleurs::class, 'quantite')->withPivot('tailles_id', 'quantite');
    }
    public function Genre()
    {
        return $this->belongsTo(genre::class,'genre_id');
    }
    public function setMotsClesAttribute($value)
    {
        $this->attributes['mots_cles'] = implode(',', array_unique(preg_split('/\s*,\s*/', $value)));
    }
    public function promos()
    {
        return $this->belongsTo(Promos::class, 'promo_id');
    }
    public function sousCategories()
{
    return $this->belongsTo(SousCategories::class,'souscategories_id');
}
public function quantitedisponible()
    {
        return $this->hasMany(quantitedisponible::class,'produits_id');
    }
public function paniers()
{
    return $this->belongsToMany(paniers::class, 'paniersproduits')
                ->withPivot('quantite', 'taille', 'couleur','prix_total');

}
public function magasins()
{
    return $this->belongsToMany(Magasins::class,'quantite', 'magasin_id', 'produits_id')->withPivot('taille', 'couleur','quantite');
}

public function images()
{
    return $this->hasMany(Images::class, 'produit_id');
}
public function scopeSearchBySousCategorie($query, $sousCategorieId)
{
    return $query->whereHas('sousCategories', function ($query) use ($sousCategorieId) {
        $query->where('id', $sousCategorieId);
    });
}

public function scopeFilterByCategory($query, $categoryId)
{
    return $query->where('categorie_id', $categoryId);
}

public function scopeFilterByGenre($query, $genreId)
{
    return $query->where('genre_id', $genreId);
}

public function scopeFilterByPriceRange($query, $minPrice, $maxPrice)
{
    return $query->whereBetween('prix', [$minPrice, $maxPrice]);
}

public function scopeFilterByColor($query, $colorId)
{
    return $query->whereHas('couleurs', function ($query) use ($colorId) {
        $query->where('couleurs.id', $colorId);
    });
}

public function scopeFilterBySize($query, $sizeId)
{
    return $query->whereHas('tailles', function ($query) use ($sizeId) {
        $query->where('tailles.id', $sizeId);
    });
}

public function scopeFilterByKeyword($query, $keyword)
{
    return $query->where('mots_cles', 'like', '%' . $keyword . '%');
}
public function commandes()
{
    return $this->belongsToMany(Commandes::class, 'commandesproduits')
                ->withPivot('quantite', 'taille', 'couleur', 'prix_total');
}

}
