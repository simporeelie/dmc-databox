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
            ['name' => 'CBI Bénin',        'country' => 'Bénin'],
            ['name' => 'CBI Burkina Faso', 'country' => 'Burkina Faso'],
            ['name' => 'CBI Côte d\'Ivoire','country' => 'Côte d\'Ivoire'],
            ['name' => 'CBI Guinée',       'country' => 'Guinée'],
            ['name' => 'CBI Guinée Bissau','country' => 'Guinée Bissau'],
            ['name' => 'CBI Mali',         'country' => 'Mali'],
            ['name' => 'CBI Niger',        'country' => 'Niger'],
            ['name' => 'CBI Sénégal',      'country' => 'Sénégal'],
            ['name' => 'CBI Togo',         'country' => 'Togo'],
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
