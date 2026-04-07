<?php

namespace Database\Seeders;

use App\Models\Entity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EntitySeeder extends Seeder
{
    public function run(): void
    {
        $entities = [
            'Coris Holding',
            'Coris Bank International',
            'CBI Baraka',
            'Coris Méso Finance',
        ];

        foreach ($entities as $name) {
            Entity::create([
                'name'      => $name,
                'slug'      => Str::slug($name),
                'is_active' => true,
            ]);
        }
    }
}
