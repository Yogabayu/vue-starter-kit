<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Pantai', 'icon' => 'waves'],
            ['name' => 'Gunung', 'icon' => 'mountain'],
            ['name' => 'Air Terjun', 'icon' => 'waterfall'],
            ['name' => 'Kuliner', 'icon' => 'utensils'],
            ['name' => 'Sejarah', 'icon' => 'landmark'],
            ['name' => 'Keluarga', 'icon' => 'family'],
        ];

        foreach ($categories as $c) {
            Category::updateOrCreate(
                ['slug' => Str::slug($c['name'])],
                ['name' => $c['name'], 'icon' => $c['icon']]
            );
        }
    }
}

