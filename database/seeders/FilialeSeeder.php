<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\Filiale;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FilialeSeeder extends Seeder
{
    public function run(): void
    {
        $cbi = Entity::where('slug', 'coris-bank-international')->first();

        $filiales = [
            ['name' => 'Bénin',         'country' => 'Bénin'],
            ['name' => 'Burkina Faso',  'country' => 'Burkina Faso'],
            ['name' => 'Côte d\'Ivoire','country' => 'Côte d\'Ivoire'],
            ['name' => 'Guinée',        'country' => 'Guinée'],
            ['name' => 'Guinée Bissau', 'country' => 'Guinée Bissau'],
            ['name' => 'Mali',          'country' => 'Mali'],
            ['name' => 'Niger',         'country' => 'Niger'],
            ['name' => 'Sénégal',       'country' => 'Sénégal'],
            ['name' => 'Tchad',         'country' => 'Tchad'],
            ['name' => 'Togo',          'country' => 'Togo'],
        ];

        foreach ($filiales as $filiale) {
            Filiale::create([
                'name'      => $filiale['name'],
                'slug'      => Str::slug($filiale['name']),
                'country'   => $filiale['country'],
                'entity_id' => $cbi->id,
                'is_active' => true,
            ]);
        }
    }
}
