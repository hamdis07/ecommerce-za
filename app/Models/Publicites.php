<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publicites extends Model
{
    use HasFactory;
    protected $table='publicites';
    protected $fillable = [
        'nom',  'detail', 'date_lancement', 'date_fin', 'montant_paye', 'image', 'video', 'affiche'
    ];
}
