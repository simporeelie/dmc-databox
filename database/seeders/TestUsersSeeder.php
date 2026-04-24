<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\Filiale;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $entity  = Entity::where('slug', 'coris-bank-international')->first();
        $filiale = Filiale::where('slug', 'burkina-faso')->first();

        // DMC user
        User::firstOrCreate(
            ['email' => 'dmc@dmc-databox.com'],
            [
                'name'      => 'Utilisateur DMC',
                'password'  => Hash::make('Dmc@2025'),
                'role'      => 'dmc',
                'entity_id' => $entity?->id,
                'is_active' => true,
            ]
        );

        // RMC user (scoped to Burkina Faso filiale)
        User::firstOrCreate(
            ['email' => 'rmc@dmc-databox.com'],
            [
                'name'      => 'Utilisateur RMC',
                'password'  => Hash::make('Rmc@2025'),
                'role'      => 'rmc',
                'entity_id' => $entity?->id,
                'filiale_id' => $filiale?->id,
                'is_active' => true,
            ]
        );

        // Visiteur user
        User::firstOrCreate(
            ['email' => 'visiteur@dmc-databox.com'],
            [
                'name'      => 'Utilisateur Visiteur',
                'password'  => Hash::make('Visiteur@2025'),
                'role'      => 'visiteur',
                'entity_id' => $entity?->id,
                'is_active' => true,
            ]
        );
    }
}
