<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            "superadmin",
            "admin",
            "client" ,
            "operateur",
            "dispatcheur",
            "responsable_marketing"
        ];

        foreach ($roles as $role) {
            // if (!Role::where('name', $role)->exists()) {
                Role::create(['name' => $role]);
            // }
        }
    }
}