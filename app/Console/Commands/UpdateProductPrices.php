<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Promos;
use App\Models\Produits;
use Carbon\Carbon;
//use Illuminate\Console\Scheduling\Schedule;

class UpdateProductPrices extends Command
{
    protected $signature = 'update:product-prices';

    protected $description = 'Met à jour les prix des produits en fonction des promotions actives';

    public function handle()
    {
        $today = Carbon::today();

        // Activer les promotions qui commencent aujourd'hui
        $promotionsAActiver = Promos::where('date_debut', $today)->get();
        foreach ($promotionsAActiver as $promotion) {
            foreach ($promotion->produits as $produit) {
                $nouveauPrix = $produit->prix_initial * (1 - $promotion->pourcentage_reduction / 100);
                $produit->prix = $nouveauPrix;
                $produit->save();
            }
        }

        // Désactiver les promotions qui se terminent aujourd'hui
        $promotionsAExpirer = Promos::where('date_fin', $today)->get();
        foreach ($promotionsAExpirer as $promotion) {
            foreach ($promotion->produits as $produit) {
                $produit->prix = $produit->prix_initial;
                $produit->save();
            }
        }

        $this->info('Les prix des produits ont été mis à jour.');
    }
    // public function schedule(Schedule $schedule)
    // {
    //     // Planifie l'exécution quotidienne de la commande
    //     $schedule->command(static::class)->daily();
    // }
}
