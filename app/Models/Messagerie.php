<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Messagerie extends Model
{
    use HasFactory;

    protected $table = "messageries";

    protected $fillable = [
        'user_id',       // L'utilisateur qui a envoyé le message
        'objet',         // Objet du message
        'nom',           // Nom de l'expéditeur
        'prenom',        // Prénom de l'expéditeur
        'telephone',     // Téléphone de l'expéditeur
        'email',         // Email de l'expéditeur
        'sujet',         // Sujet du message
        'description',   // Contenu du message
        'read',          // Indicateur si le message a été lu ou non
        'attachments',   // Pièces jointes au format JSON
    ];

    protected $casts = [
        'read' => 'boolean',
        'attachments' => 'array',  // Les pièces jointes seront traitées sous forme de tableau
    ];

    /**
     * Relation avec l'utilisateur qui a envoyé le message.
     */
   
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relation avec les réponses envoyées à ce message.
     */
    public function messagesEnvoyes()
    {
        return $this->hasMany(MessageEnvoyer::class, 'message_id');
    }
    public static function getMessagesByUserId($userId)
    {
        return self::where('user_id', $userId);
    }
    public static function getAllMessagesByUserId($userId)
{
    // Récupérer tous les messages reçus par l'utilisateur (où il est destinataire)
    $messagesRecus = self::where('user_id', $userId)->orderBy('created_at', 'asc') // Trier par date croissante (asc) ou décroissante (desc)
    ->get();


    // Récupérer tous les messages envoyés par l'utilisateur (où il a répondu à des messages)
    $messagesEnvoyes = MessageEnvoyer::where('user_id', $userId)->with('messagerie')->orderBy('created_at', 'asc') // Trier par date croissante (asc) ou décroissante (desc)
    ->get();

    // Vous pouvez retourner un tableau contenant les deux collections
    return [
        'messages_recus' => $messagesRecus,
        'messages_envoyes' => $messagesEnvoyes
    ];
}
}
