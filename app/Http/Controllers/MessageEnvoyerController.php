<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Models\MessageEnvoyer;
use App\Models\Messagerie;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageEnvoyerController extends Controller
{
        public function showMessage($id)
    {  $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $message = Messagerie::with('user')->findOrFail($id);
        return response()->json($message);
    }

    // Répondre à un message
    public function replyToMessage(Request $request, $idMessage)
    {
        // Vérifier si l'utilisateur est authentifié
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();

        // Valider la requête entrante
        $request->validate([
            'content' => 'required|string',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
        ]);

        try {
            // Trouver le message original
            $message = Messagerie::findOrFail($idMessage);

            // Vérifier si le message appartient à l'utilisateur authentifié
            if ($message->user_id !== $user->id) {
                return response()->json(['error' => 'You can only reply to your own messages.'], 403);
            }

            // Créer la réponse
            $reply = new MessageEnvoyer([
                'user_id' => $user->id,
                'message_id' => $message->id, // Associer au message original
                'content' => $request->content,
            ]);

            // Gérer les fichiers joints
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('uploads', 'public');
                $reply->file_path = $filePath; // Enregistrer le chemin du fichier
            }

            // Sauvegarder la réponse
            $reply->save();

            // Mettre à jour le message original pour indiquer qu'il a été répondu
            $message->update(['replied' => true]);

            return response()->json([
                'message' => 'Réponse ajoutée avec succès au message.',
                'reply' => $reply
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Message not found.'], 404);
        } catch (\Exception $e) {
            Log::error('Error replying to message: ' . $e->getMessage());
            return response()->json(['error' => 'Une erreur s\'est produite lors de l\'ajout de la réponse au message.'], 500);
        }
    }

    // Supprimer un message
    public function deleteMessage($id)
    {     $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $message = Messagerie::findOrFail($id);
        $message->delete();
        return response()->json('Message supprimé avec succès.', 200);
    }
    public function listAdmins()
{     $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $admins = User::where('role', '!=', 'client')->get();
    return response()->json($admins);
}

    // Lister les clients
    public function listClients()
    {     $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $clients = User::where('role', 'client')->get();
        return response()->json($clients);
    }

    // Lister tous les messages
    public function listMessages()
    {     $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $messages = Messagerie::with('user')->get();
        return response()->json($messages);
    }

    // Lister les messages non lus
    public function listUnreadMessages()
    {     $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $messages = Messagerie::where('read', false)->with('user')->get();
        return response()->json($messages);
    }


    // Lister les messages lus
    public function listReadMessages()
    {     $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $messages = Messagerie::where('read', true)->with('user')->get();
        return response()->json($messages);
    }

    // Rechercher des messages par nom d'utilisateur
    public function searchMessages(Request $request)
    {     $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate(['username' => 'required|string']);

        $user = User::where('user_name', 'like', '%' . $request->username . '%')->first();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé.'], 404);
        }

        $messages = Messagerie::where('user_id', $user->id)->with('user')->get();
        return response()->json($messages);
    }

    // Envoyer un message à un client spécifique (uniquement pour admin)
    public function sendMessageToClient(Request $request, $user_id)
    {     $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        // Valider les données entrantes
        $request->validate([
            'content' => 'required|string',
            'files.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx,xls,xlsx|max:2048',
        ]);

        // Convertir l'user_id en tableau si plusieurs IDs sont fournis
        $user_ids = explode(',', $user_id);

        foreach ($user_ids as $id) {
            $id = trim($id); // Enlever les espaces
            $user = User::find($id); // Récupérer l'utilisateur par ID

            if (!$user) {
                return response()->json(['error' => "User with ID $id does not exist."], 404);
            }

            // Créer un nouveau message dans la table Messageries
            $message = Messagerie::create([
                'user_id' => $user->id,
                'objet' => '', // Remplir si nécessaire
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'telephone' => $user->telephone ?? '', // Utiliser une chaîne vide si non disponible
                'email' => $user->email ?? '',
                'sujet' => '', // Remplir si nécessaire
                'description' => $request->content,
            ]);

            // Créer l'enregistrement correspondant dans MessageEnvoyer
            $messageEnvoyer = new MessageEnvoyer([
                'user_id' => $user->id,
                'message_id' => $message->id,
                'content' => $request->content,
            ]);

            // Gérer les fichiers joints
            if ($request->hasFile('files')) {
                $filePaths = [];
                foreach ($request->file('files') as $file) {
                    $filePaths[] = $file->store('messages', 'public');
                }
                $messageEnvoyer->attachments = json_encode($filePaths);
            }

            $messageEnvoyer->save();
        }

        return response()->json('Message envoyé aux clients avec succès.', 200);
    }

    // Contacter l'admin
    public function contactAdmin(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'files.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx,xls,xlsx|max:2048',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Non autorisé'], 401);
        }

        if ($user->status === 'blocked') {
            return response()->json(['message' => 'Votre compte est bloqué. Vous ne pouvez pas contacter l\'admin.'], 403);
        }

        // Créer un nouveau message pour contacter l'admin
        $message = new Messagerie([
            'user_id' => $user->id,
            'objet' => 'Contact Admin', // Définir un objet approprié
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'telephone' => $user->numero_telephone,
            'email' => $user->email,
            'sujet' => 'Contact', // Définir si nécessaire
            'description' => $request->content,
        ]);

        // Gérer les fichiers joints
        if ($request->hasFile('files')) {
            $filePaths = [];
            foreach ($request->file('files') as $file) {
                $filePaths[] = $file->store('messages', 'public');
            }
            $message->attachments = json_encode($filePaths);
        }

        $message->save();
        return response()->json('Message envoyé à l\'admin avec succès.', 200);
    }

    // Bloquer un utilisateur
    public function blockUser($userId)
    {     $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $user = User::findOrFail($userId);
        $user->update(['status' => 'blocked']);
        return response()->json(['message' => 'L\'utilisateur a été bloqué avec succès.'], 200);
    }

    // Débloquer un utilisateur
    public function unblockUser($userId)
    {     $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $user = User::findOrFail($userId);
        $user->update(['status' => 'active']);
        return response()->json(['message' => 'L\'utilisateur a été débloqué avec succès.'], 200);
    }

    // Voir les messages d'un utilisateur
    public function viewUserMessages(Request $request)
    {
        // Récupérer l'ID de l'utilisateur authentifié
        $userId = $request->user()->id;

        // Récupérer les messages envoyés par cet utilisateur
        $messagesEnvoyes = MessageEnvoyer::where('user_id', $userId)->with('message')->get();

        return response()->json($messagesEnvoyes);
    }
    public function markAsRead($messageId)
{     $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $message = Messagerie::findOrFail($messageId);
    $message->update(['read' => true]);

    return response()->json(['message' => 'Message marked as read.']);
}

}
