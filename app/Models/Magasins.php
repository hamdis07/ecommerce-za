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
        return $this->belongsToMany(Produits::class, 'quantite', 'magasin_id', 'produits_id')
                    ->withPivot('tailles_id', 'couleurs_id', 'quantite')  // Utilisez les colonnes correctes de la table `quantite`
                    ->join('tailles', 'quantite.tailles_id', '=', 'tailles.id')  // Jointure avec la table `tailles`
                    ->join('couleurs', 'quantite.couleurs_id', '=', 'couleurs.id')  // Jointure avec la table `couleurs`
                    ->select('Produits.*', 'quantite.magasin_id', 'tailles.nom as taille', 'couleurs.nom as couleur', 'quantite.quantite');  // Sélection des colonnes avec les noms corrects
    }


}
