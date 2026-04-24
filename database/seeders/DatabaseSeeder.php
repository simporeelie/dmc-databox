<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EntitySeeder::class,
            FilialeSeeder::class,
            CategorySeeder::class,
            AdminUserSeeder::class,
            TestUsersSeeder::class,
        ]);
    }
}
