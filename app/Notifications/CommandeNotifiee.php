<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Commandes;

class CommandeNotifiee extends Notification
{
    use Queueable;

    public $commande;

    public function __construct(Commandes $commande)
    {
        $this->commande = $commande;
    }

    public function via($notifiable)
    {
        return ['mail','database']; // Vous pouvez définir d'autres canaux comme 'database', 'slack', etc.
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('Une nouvelle commande a été passée.')
            ->action('Voir la commande', url('/'))
            ->line('Merci de votre attention !');
    }
    public function toDatabase($notifiable)
    {
        return [
            'commande_id' => $this->commande->id,
            'montant_total' => $this->commande->montant_total,
            'statut' => $this->commande->statut,
        ];
    }

    public function toArray($notifiable)
    {
        return [
            'commande_id' => $this->commande->id,
            'montant_total' => $this->commande->montant_total,
            'statut' => $this->commande->statut,
        ];
    }

    // D'autres méthodes pour d'autres canaux comme 'database', 'slack', etc.
}
