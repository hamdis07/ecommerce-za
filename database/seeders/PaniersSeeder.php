<?php

namespace Database\Seeders;

use App\Models\Paniers;
use App\Models\Produits;
use App\Models\PaniersProduits;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class PaniersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = Faker::create();

        // Let's assume we have some products and users already in the database
        $users = User::all();
        $produits = Produits::all();

        // Loop through the users to create paniers for each one
        foreach ($users as $user) {
            // Create a Panier for the user
            $panier = Paniers::create([
                'user_id' => $user->id,
            ]);

            // Randomly select 1 to 5 products to add to the panier
            $selectedProduits = $produits->random(rand(1, 5));

            foreach ($selectedProduits as $produit) {
                // Add each product to the panier with a random quantity, size, and color
                PaniersProduits::create([
                    'panier_id' => $panier->id,
                    'produit_id' => $produit->id,
                    'quantite' => $faker->numberBetween(1, 5),
                    'taille' => $faker->randomElement(['S', 'M', 'L', 'XL']),
                    'couleur' => $faker->safeColorName(),
                    'prix_total' => $faker->randomFloat(2, 10, 500) // Fake price between 10 and 500
                ]);
            }
        }
    }
}
