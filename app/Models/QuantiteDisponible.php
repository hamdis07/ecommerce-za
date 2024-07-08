<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuantiteDisponible extends Model
{
    use HasFactory;
    protected $table = 'quantite';

    public $timestamps = false;

    protected $fillable = ['produits_id', 'tailles_id', 'couleurs_id', 'quantite','magasin_id'];

    public function magasin()
    {
        return $this->belongsTo(magasins::class, 'magasin_id');
    }

    public function produits()
    {
        return $this->belongsTo(Produits::class, 'produits_id');
    }
     public function tailles()
    {
        return $this->belongsTo(tailles::class, 'tailles_id');
    }
    public function couleurs()
    {
        return $this->belongsTo(couleurs::class, 'couleurs_id');
    }
}
