<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;
use App\Models\Configuration;
class ConfigurationSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table('configuration')->insert([
            'key' => 'frais_livraison',
            'value' => 7.00,  // Valeur par défaut pour les frais de livraison
        ]);
    }
}
