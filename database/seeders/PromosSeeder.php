<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Promos;
use Faker\Factory as Faker;

class PromosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        for ($i = 1; $i <= 10; $i++) {
            Promos::create([
                'nom' => $faker->word(),
                'pourcentage_reduction' => $faker->randomFloat(2, 5, 50), // Random discount percentage between 5% and 50%
                'date_debut' => $faker->dateTimeBetween('-1 month', 'now'), // Random start date between last month and today
                'date_fin' => $faker->dateTimeBetween('now', '+1 month'), // Random end date between today and next month
            ]);
        }
    }
}
