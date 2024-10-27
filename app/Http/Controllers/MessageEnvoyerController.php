<?php

namespace App\Http\Controllers;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;  // Correct import for JsonResponse

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use App\Models\MessageEnvoyer;
use App\Models\Messagerie;

class MessageEnvoyerController extends Controller
{
    public function getMessagesReceived()
    {
        $user = Auth::user();

        // Vérifier si l'utilisateur est authentifié
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Récupérer les messages envoyés par l'utilisateur connecté
        $messages = MessageEnvoyer::with([ 'user:id,user_name,user_image']) // Récupérer les réponses et les détails de l'utilisateur
            ->where('user_id', $user->id) // Filtrer les messages par ID utilisateur
            ->get();

        if ($messages->isEmpty()) {
            return response()->json(['message' => 'No messages found.'], 409);
        }

        $messages->each(function ($message) {
            $message->user_name = $message->user->user_name; // Ajouter le nom de l'utilisateur
            $message->user_image = $message->user->user_image; // Ajouter l'image de l'utilisateur
            unset($message->user); // Optionnel : enlever la relation 'user' pour nettoyer la réponse
        });

        return response()->json($messages);
    }
        public function showMessage($id)
    {  $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $message = Messagerie::with('user')->findOrFail($id);
        return response()->json($message);
    }
    public function contactAdmin(Request $request)
{
    $request->validate([
        'objet' => 'required|string|max:255',
        'content' => 'required|string',
        'file.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx,xls,xlsx|max:2048',
    ]);

    $user = Auth::user();
    if (!$user) {
        return response()->json(['message' => 'Non autorisé'], 401);
        }

        if ($user->status === 'bloqué') {
            return response()->json(['message' => 'Votre compte est bloqué. Vous ne pouvez pas contacter l\'admin.'], 403);
            }

            // Créer un nouveau message pour contacter l'admin
            $message = new Messagerie([
                'user_id' => $user->id,
                'objet' => $request->objet,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'telephone' => $user->numero_telephone,
                'email' => $user->email,
                'sujet' => 'Contact',
                'description' => $request->content,
                'attachments'=>$request->file,
                ]);

                // Gérer les fichiers joints
                // dd($request->hasFile('file'));
                if ($request->hasFile('file')) {
                    $filePaths = [];
                    // Log de débogage pour voir les fichiers reçus
                    foreach ($request->file('file') as $file) {
                        // Vérifiez que le fichier est bien un UploadedFile avant de logger
                        if ($file instanceof \Illuminate\Http\UploadedFile) {
                            \Log::info('Fichier reçu: ', [
                                'name' => $file->getClientOriginalName(),
                                'size' => $file->getSize(),
                                'mime' => $file->getMimeType(),
                                ]);
                                }
                                }

                                // Récupérer les fichiers envoyés
                                $files = $request->file('file');

                                // Vérifier si $files est un tableau
                                if (is_array($files)) {
                                    foreach ($files as $file) {
                                        // Vérifier si le fichier est valide
                                        if ($file->isValid()) {
                                            // Créer un nom de fichier unique
                                            $fileName = time() . '_' . $file->getClientOriginalName();
                                            // Déplacer le fichier vers le répertoire public/messages
                                            $filePath = $file->move(public_path('messages'), $fileName);
                                            // Créer l'URL du fichier
                                            $fileUrl = asset('messages/' . $fileName);
                                            // Ajouter l'URL au tableau
                    $filePaths[] = $fileUrl;
                    // Journaliser l'URL du fichier sauvegardé
                    \Log::info('Fichier sauvegardé: ' . $fileUrl);
                } else {
                    \Log::warning('Le fichier n\'est pas valide: ' . $file->getClientOriginalName());
                }
            }

            // Vérifier si le tableau des chemins n'est pas vide avant de l'encoder
            if (!empty($filePaths)) {
                // Convertir les chemins des fichiers en JSON
                $message->attachments = json_encode($filePaths);
            } else {
                \Log::warning('Aucun fichier n\'a été sauvegardé.');
            }
        } else {
            \Log::warning('Le fichier n\'est pas un tableau.');
        }
    }

    // Sauvegarder le message
    $message->save();

    return response()->json('Message envoyé à l\'admin avec succès.', 200);
}



    public function replyToMessage(Request $request, $idMessage)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Non autorisé'], 401);
        }

        $request->validate([
            'content' => 'required|string',
            'file.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx,xls,xlsx|max:2048',
        ]);

        $message = Messagerie::find($idMessage);
        if (!$message) {
            return response()->json(['message' => 'Message introuvable'], 404);
        }

        $messageEnvoyer = new MessageEnvoyer([
            'user_id' => $user->id,
            'message_id' => $message->id,
            'content' => $request->content,
        ]);

        if ($request->hasFile('file')) {
            $filePaths = [];
            foreach ($request->file('file') as $file) {
                $filePath = $file->store('messages', 'public');
                $filePaths[] = Storage::url($filePath);
            }
            $messageEnvoyer->attachments = json_encode($filePaths);
        }

        $messageEnvoyer->save();

        return response()->json(['message' => 'Réponse envoyée avec succès.'], 200);
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
    public function listAdmins(Request $request)
    {
        // Vérifiez les rôles de l'utilisateur authentifié
        $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Récupérer le rôle 'client'
        $clientRole = Role::where('name', 'client')->first();

        if (!$clientRole) {
            return response()->json(['message' => 'Client role not found'], 404);
        }

        // Récupérer tous les administrateurs (sans pagination) en excluant le rôle 'client'
        $admins = User::whereDoesntHave('roles', function ($query) use ($clientRole) {
            $query->where('role_id', $clientRole->id);
        })->get();

        // Retourner les données
        return response()->json([
            'data' => $admins
        ]);
    }
public function getClients(Request $request)
{   $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $perPage = $request->input('per_page', 10); // Number of items per page


    $clientRole = Role::where('name', 'client')->first();

    if (!$clientRole) {
        return response()->json([
            'message' => 'Client role not found',
        ], 404);
    }

    $clients = User::whereHas('roles', function($query) use ($clientRole) {
        $query->where('role_id', $clientRole->id);
    })->paginate($perPage);

    return response()->json([
        'data' => $clients->items(),
        'current_page' => $clients->currentPage(),
        'total_pages' => $clients->lastPage(),
        'total_items' => $clients->total()
    ]);
}
public function listAllUsers(Request $request)
{
    // Vérifiez les rôles de l'utilisateur authentifié (si vous voulez restreindre cette action aux administrateurs)
    $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Récupérer tous les utilisateurs, peu importe leur rôle
    $users = User::with('roles')->get(); // Inclure les rôles dans la réponse

    // Retourner les données
    return response()->json([
        'data' => $users
    ]);
}

    // Lister les clients
    public function listClients(Request $request)
    {
        // Vérifiez les rôles de l'utilisateur authentifié
        $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Trouver le rôle client
        $clientRole = Role::where('name', 'client')->first();

        if (!$clientRole) {
            return response()->json([
                'message' => 'Client role not found',
            ], 404);
        }

        // Récupérer tous les clients sans pagination
        $clients = User::whereHas('roles', function ($query) use ($clientRole) {
            $query->where('role_id', $clientRole->id);
        })->get();

        // Retourner les données
        return response()->json([
            'data' => $clients
        ]);
    }

    // Lister tous les messages
    public function listMessages()
    {
        $user = Auth::user();
        $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Eager load the 'user' relationship and select additional fields
        $messages = Messagerie::with(['user:id,user_name,user_image']) // Assuming the relationship is defined in the Messagerie model
            ->get();

        // Append the user details to each message for the response
        $messages->each(function ($message) {
            $message->user_name = $message->user->user_name;
            $message->user_image = $message->user->user_image;
            unset($message->user); // Optional: Remove the 'user' relationship to clean up the response
        });

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
{
    $user = Auth::user();
    $roles = ['admin', 'superadmin', 'dispatcheur', 'operateur', 'responsable_marketing'];

    if (!$user || !$user->hasAnyRole($roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $request->validate([
        'content' => 'required|string',
        'file.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx,xls,xlsx|max:2048',
    ]);

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
        if ($request->hasFile('file')) {
            $filePaths = [];
            foreach ($request->file('file') as $file) {
                // Stocker le fichier et obtenir le chemin
                $path = $file->store('messages', 'public');
                // Générer l'URL complète pour le fichier
                $filePaths[] = Storage::url($path);
            }
            $messageEnvoyer->attachments = json_encode($filePaths); // Stocker les chemins des fichiers en JSON
        }

        $messageEnvoyer->save();
    }

    return response()->json('Message envoyé aux clients avec succès.', 200);
}


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
    {$user = Auth::user();
        // Récupérer l'ID de l'utilisateur authentifié
        $userId = $request->user()->id;

        // Récupérer les messages envoyés par cet utilisateur
        $messagesEnvoyes = MessageEnvoyer::where('user_id', $userId)->with('messagerie')->get();

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
}public function getMessageById($id)
{
    // Check if the user is authenticated
    $user = Auth::user();
    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    try {
        // Retrieve the message by its ID
        $message = Messagerie::with('user')->findOrFail($id);

        // Check if the message belongs to the authenticated user
        if ($message->user_id !== $user->id && !$user->hasAnyRole(['admin', 'superadmin'])) {
            return response()->json(['message' => 'You do not have permission to view this message.'], 403);
        }

        // Return the message details
        return response()->json([
            'message' => 'Message retrieved successfully.',
            'data' => $message
        ], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['error' => 'Message not found.'], 404);
    } catch (\Exception $e) {
        // Log the error for further debugging
        Log::error('Error retrieving message: ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred while retrieving the message.'], 500);
    }
}
public function getConversationWithUser($userId)
{
    $authUser = Auth::user();

    if (!$authUser) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Fetch messages between the authenticated user and the specified user
    $messages = Messagerie::where(function ($query) use ($authUser, $userId) {
            $query->where('user_id', $authUser->id)
                  ->orWhere('user_id', $userId);
        })
        ->with('user:id,user_name,user_image') // Assuming the user relationship is defined in Messagerie model
        ->orderBy('created_at', 'asc') // Order messages by creation date
        ->get();

    return response()->json($messages);
}

public function getMessagesByUser($userId)
{
    $messages = Messagerie::getAllMessagesByUserId($userId);

    return response()->json($messages);  // Retourner sous forme de JSON pour une API, ou l'envoyer à une vue
}


public function getUsersWithLastMessages(): JsonResponse
{
    // Get all users who have either sent or received a message
    $usersWithLastMessages = User::with(['messageries' => function($query) {
        // Fetch the last received message
        $query->orderBy('created_at', 'desc');
    }, 'messageEnvoyes' => function($query) {
        // Fetch the last sent message
        $query->orderBy('created_at', 'desc');
    }])->get();

    // Prepare the data to return, including the latest message (either sent or received)
    $response = $usersWithLastMessages->map(function ($user) {
        $lastReceivedMessage = $user->messageries->first();
        $lastSentMessage = $user->messageEnvoyes->first();

        // Determine which is the latest message
        $latestMessage = $lastReceivedMessage && $lastSentMessage
            ? ($lastReceivedMessage->created_at > $lastSentMessage->created_at ? $lastReceivedMessage : $lastSentMessage)
            : ($lastReceivedMessage ?: $lastSentMessage);

        // Determine message content based on the type of message
        $lastMessageContent = null;
        $messageType = null;

        if ($latestMessage instanceof Messagerie) {
            $lastMessageContent = $latestMessage->description; // Received message
            $messageType = 'received';
        } elseif ($latestMessage instanceof MessageEnvoyer) {
            $lastMessageContent = $latestMessage->content; // Sent message
            $messageType = 'sent';
        }

        return [
            'user_id' => $user->id,
            'user_name' => $user->user_name,
            'user_image' => $user->user_image,
            'last_message_content' => $lastMessageContent,
            'last_message_time' => $latestMessage ? $latestMessage->created_at : null,
            'message_type' => $messageType,
        ];
    });

    // Sort the users by the timestamp of their latest message (most recent first)
    $sortedResponse = $response->sortByDesc('last_message_time')->values();

    return response()->json($sortedResponse);
}

}
