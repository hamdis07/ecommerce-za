<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Messagerie extends Model
{
    use HasFactory;
protected $table="messageries";
    protected $fillable = [
        'user_id',
        'objet',
        'nom',
        'prenom',
        'telephone',
        'email',
        'sujet',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
