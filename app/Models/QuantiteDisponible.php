<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuantiteDisponible extends Model
{
    use HasFactory;
    protected $table = 'quantite';

    public $timestamps = false;

    protected $fillable = ['produits_id', 'tailles_id', 'couleurs_id', 'quantite', 'magasin_id'];

    public function magasin()
    {
        return $this->belongsTo(Magasins::class, 'magasin_id');
    }

    public function produits()
    {
        return $this->belongsTo(Produits::class, 'produits_id');
    }

    public function tailles()
    {
        return $this->belongsTo(Tailles::class, 'tailles_id');
    }

    public function couleurs()
    {
        return $this->belongsTo(Couleurs::class, 'couleurs_id');
    }
}
