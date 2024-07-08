<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

use App\Models\HasRoles;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
          // Create a user
          $user = User::create([
            'nom' => 'Doe', // Ajoutez le nom de famille
            'prenom' => 'John', // Ajoutez le prénom
            'genre' => 'Homme', // Ajoutez le genre (par exemple Homme/Femme)
            'date_de_naissance' => '1990-01-01', // Ajoutez la date de naissance au format 'AAAA-MM-JJ'
            'Addresse' => '123 Rue de la Rue', // Ajoutez l'adresse
            'occupation' => 'Développeur', // Ajoutez l'occupation
            'etat_social' => 'Célibataire', // Ajoutez l'état social
            'numero_telephone' => '0123456789', // Ajoutez le numéro de téléphone
            'user_name' => 'john_doe44', // Ajoutez le nom d'utilisateur
            'email' => 'johnjjj@444example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'user_image'=>'https://via.placeholder.com/150',
        ]);

        // Assign a role to the user
        $role = Role::where('name', 'admin')->first(); // Changez 'admin' par le rôle désiré
        $user->assignRole($role);
    }
}
