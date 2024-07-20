<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LivraisonDetails extends Model
{
    use HasFactory;
    protected $table='livraisondetails';
    protected $fillable = [
        'user_id', 'commandes_id', 'adresse', 'ville', 'code_postal','description','telephone'
        // Ajoutez d'autres attributs au besoin
    ];

    // Relation avec l'utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation avec la commande
    public function commande()
    {
        return $this->belongsTo(Commandes::class,'commandes_id')->withDefault();
    }

}
