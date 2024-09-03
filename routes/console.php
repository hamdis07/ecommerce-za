<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();
Schedule::call(function () {
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
})->daily();
