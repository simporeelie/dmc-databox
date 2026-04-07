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
                'name' => 'Documents',
                'icon' => 'document',
                'subcategories' => [
                    'Branding',
                    'Coris Holding News',
                    'Coris News',
                    'Rapports d\'activités',
                    'Stratégies',
                ],
            ],
            [
                'name' => 'Graphisme',
                'icon' => 'photo',
                'subcategories' => [
                    'Books visuels',
                    'Chartes graphiques',
                    'Visuels commerciaux',
                    'Visuels digitaux',
                    'Visuels institutionnels',
                    'Editions de fin d\'années (EDFA)',
                    'Évènements divers',
                ],
            ],
            [
                'name' => 'Audio Visuels',
                'icon' => 'film',
                'subcategories' => [
                    'Bande annonce',
                    'Capsules',
                    'Publireportages',
                    'Spots audio',
                    'Spots vidéo',
                    'Tags',
                    'Tutoriels',
                ],
            ],
            [
                'name' => 'Produits et Services',
                'icon' => 'tag',
                'subcategories' => [
                    'Coris Money',
                    'MyCorisBank',
                    'RapidEx New',
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
