<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Produits;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ProduitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        for ($i = 1; $i <= 50; $i++) {
            Produits::create([
                'references' => $faker->unique()->numerify('REF###'),
                'nom_produit' => $faker->word(),
                'image_url' => $faker->imageUrl(640, 480, 'products', true, 'Faker'),
                'description' => $faker->sentence(),
                'prix' => $faker->randomFloat(2, 10, 1000),
                'prix_initial' => $faker->randomFloat(2, 10, 1000),
                'composition' => $faker->word(),
                'entretien' => $faker->sentence(),
                'mots_cles' => $faker->words(3, true),
                'is_featured' => $faker->boolean(),
                'is_hidden' => $faker->boolean(),
                'categorie_id' => rand(1, 5), // Assuming you have categories with IDs 1 to 5
                'genre_id' => rand(1, 3), // Assuming you have genres with IDs 1 to 3
                'promo_id' => rand(1,3), // Assuming you have promos with IDs 1 to 5
                'souscategories_id' => rand(1, 5), // Assuming subcategories with IDs 1 to 5
            ]);
        }
    }
}
