<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['slug' => 'popular', 'name' => 'Popular'],
            ['slug' => 'family', 'name' => 'Family'],
            ['slug' => 'nature', 'name' => 'Nature'],
        ];
        foreach ($items as $it) {
            Tag::updateOrCreate(['slug' => $it['slug']], $it);
        }
    }
}

