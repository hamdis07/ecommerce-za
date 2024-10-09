<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Commandes;
use App\Models\User;
use App\Models\Produits;
use App\Models\Paniers;
use App\Models\CommandesProduits;
use Faker\Factory as Faker;

class CommandesSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        // Generate 10 fake commandes
        for ($i = 0; $i < 10; $i++) {
            // Fetch a random user and panier for the commande
            $user = User::inRandomOrder()->first();
            $panier = Paniers::inRandomOrder()->first();

            // Create a fake commande
            $commande = Commandes::create([
                'user_id' => $user->id,
                'panier_id' => $panier->id,
                'montant_total' => 0, // We'll calculate this later
                'statut' => $faker->randomElement(['pending', 'completed', 'canceled']),
                'paiement_id' => null, // Assuming payment details are handled elsewhere
                'methode_paiement' => $faker->randomElement(['credit_card', 'paypal', 'bank_transfer'])
            ]);

            // Fetch random products to associate with this commande
            $produits = Produits::inRandomOrder()->take(rand(1, 5))->get();

            $montantTotal = 0;

            // Associate products with the commande and populate pivot table
            foreach ($produits as $produit) {
                $quantite = rand(1, 3);
                $prixTotal = $produit->prix * $quantite;

                // Attach the produit to the commande with pivot data
                CommandesProduits::create([
                    'commandes_id' => $commande->id,
                    'produits_id' => $produit->id,
                    'quantite' => $quantite,
                    'taille' => $faker->randomElement(['S', 'M', 'L']),
                    'couleur' => $faker->safeColorName(),
                    'prix_total' => $prixTotal
                ]);

                // Add to the total amount
                $montantTotal += $prixTotal;
            }

            // Update the total amount of the commande
            $commande->update(['montant_total' => $montantTotal]);
        }
    }
}
