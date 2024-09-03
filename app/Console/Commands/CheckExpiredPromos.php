<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Promos;
use App\Models\Produits;
use Carbon\Carbon;

class CheckExpiredPromos extends Command
{
    protected $signature = 'promos:check-expired';
    protected $description = 'Désactiver les promotions expirées';

    public function handle()
    {
        $currentDate = Carbon::now();

        $promotions = Promos::where('date_fin', '<', $currentDate)->get();

        foreach ($promotions as $promo) {
            $produits = Produits::where('promo_id', $promo->id)->get();

            foreach ($produits as $produit) {
                $produit->promo_id = null;
                $produit->prix = $produit->prix_initial;
                $produit->save();
            }
        }

        $this->info('Promotions expirées désactivées.');
    }
}
