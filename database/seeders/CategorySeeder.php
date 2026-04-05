<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Graphisme',
                'icon' => 'photo',
                'subcategories' => [
                    'Book Visuel',
                    'Chartes graphiques',
                    'Éditions de fin d\'année',
                    'Visuels commerciaux',
                    'Visuels digitaux',
                    'Visuels institutionnels',
                    'Événements spéciaux',
                ],
            ],
            [
                'name' => 'Audiovisuelle',
                'icon' => 'film',
                'subcategories' => [
                    'Capsules',
                    'Publi-reportages',
                    'Spots audio',
                    'Spots vidéo',
                    'Tags',
                    'Tutoriels',
                    'Messages des DG',
                ],
            ],
            [
                'name' => 'Documentaire',
                'icon' => 'document',
                'subcategories' => [
                    'Coris Holding News',
                    'Stratégie',
                    'Journaux internes',
                    'Rapports annuels',
                    'Rapports d\'AGO',
                    'Rapports de sponsoring',
                    'Chartes graphiques',
                    'Versions du logotype',
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $category = Category::create([
                'name' => $catData['name'],
                'slug' => Str::slug($catData['name']),
                'icon' => $catData['icon'],
            ]);

            foreach ($catData['subcategories'] as $subName) {
                Subcategory::create([
                    'name'        => $subName,
                    'slug'        => Str::slug($subName . '-' . $category->id),
                    'category_id' => $category->id,
                ]);
            }
        }
    }
}
