<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'      => 'Administrateur',
            'email'     => 'admin@dmc-databox.com',
            'password'  => Hash::make('Admin@2025'),
            'role'      => 'admin',
            'is_active' => true,
        ]);
    }
}
