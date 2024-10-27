<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageEnvoyer extends Model
{
    use HasFactory;

    protected $table = "message_envoyers";

    protected $fillable = [
        'message_id',    // ID du message principal
        'user_id',       // Utilisateur qui a envoyé la réponse
        'content',       // Contenu de la réponse
        'attachments',   // Pièces jointes au format JSON
    ];

    protected $casts = [
        'attachments' => 'array',  // Les pièces jointes seront traitées sous forme de tableau
    ];

    /**
     * Relation avec le message principal.
     */
    public function messagerie()
    {
        return $this->belongsTo(Messagerie::class, 'message_id');
    }

    /**
     * Relation avec l'utilisateur qui a envoyé cette réponse.
     */
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}



