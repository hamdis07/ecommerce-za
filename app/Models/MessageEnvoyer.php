<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageEnvoyer extends Model
{
    use HasFactory;

    protected $table="message_envoyers";
    protected $fillable = [
        'message_id',
        'user_id',
        'content',
    ];

    /**
     * Relation avec le message associé à cette réponse.
     */
    public function messagerie()
    {
        return $this->belongsTo(Messagerie::class, 'message_id');

    }
    public function user()
    {
        return $this->belongsTo(user::class);
    }
}
