<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    protected $table = 'configuration';  // Nom de la table
    protected $fillable = ['key', 'value'];  // Attributs qui peuvent être assignés en masse
    public $timestamps = true;  // Indique si le modèle utilise les timestamps
}
