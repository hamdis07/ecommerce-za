<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paiements extends Model
{
    use HasFactory;
    protected $table='paiements';
    protected $fillable = ['user_id',
        'commandes_id', 'livraisondetails_id', 'methode_paiement', 'numero_carte', 'nom_detenteur_carte',
        'mois_validite', 'annee_validite', 'code_secret', 'adresse_facturation',
         'prix_total'
    ];

    public function commande()
    {
        return $this->belongsTo(Commandes::class);
    }

    public function livraison()
    {
        return $this->belongsTo(livraisondetails::class);
    }
    public function user()
    {
        return $this->belongsTo(users::class);
    }
}
