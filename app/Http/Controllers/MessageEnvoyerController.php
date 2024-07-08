<?php
namespace App\Http\Controllers;

use App\Models\MessageEnvoyer;
use App\Models\Messagerie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageEnvoyerController extends Controller
{
    // Fonction pour afficher tous les messages reçus
    public function listMessages()
    {
        $messages = Messagerie::all();
        return response()->json($messages);
    }

    // Fonction pour afficher un message spécifique
    public function showMessage($id)
    {
        $message = Messagerie::findOrFail($id);
        return response()->json($message);
    }

    // Fonction pour répondre à un message
    public function replyToMessage(Request $request, $idMessage)
    {
        // Vérifier si l'utilisateur est authentifié
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Récupérer l'utilisateur actuellement authentifié
        $user = Auth::user();

        // Validation des données de la réponse
        $request->validate([
            'content' => 'required|string',
        ]);

        try {
            // Recherche du message par son ID
            $message = Messagerie::findOrFail($idMessage);

            // Création de la réponse associée au message
            $reply = new Messageenvoyer([
                'user_id' => $user->id,
                'message_id' => $message->id,
                'content' => $request->content,
            ]);

            // Sauvegarde de la réponse
            $reply->save();

            // Marquer le message comme ayant été répondu
            $message->update(['replied' => true]);

            return response()->json('Réponse ajoutée avec succès au message.', 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Une erreur s\'est produite lors de l\'ajout de la réponse au message.'], 500);
        }
    }

    // Fonction pour supprimer un message
    public function deleteMessage($id)
    {
        $message = Messagerie::findOrFail($id);
        $message->delete();
        return response()->json('Message supprimé avec succès.', 200);
    }

    // Fonction pour bloquer un utilisateur et supprimer tous ses messages
    public function blockUser($userId)
    {
        // Supprimez tous les messages de l'utilisateur
        Messagerie::where('user_id', $userId)->delete();
        // Ajoutez le code pour bloquer l'utilisateur si nécessaire
        // Par exemple, mettez à jour la colonne "blocked" dans la table des utilisateurs
        return response()->json('Utilisateur bloqué et ses messages supprimés.', 200);
    }
}
