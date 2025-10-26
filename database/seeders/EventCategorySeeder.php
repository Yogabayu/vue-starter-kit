<?php

namespace Database\Seeders;

use App\Models\EventCategory;
use Illuminate\Database\Seeder;

class EventCategorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['slug' => 'music', 'name' => 'Music'],
            ['slug' => 'culture', 'name' => 'Culture'],
            ['slug' => 'sports', 'name' => 'Sports'],
        ];
        foreach ($items as $it) {
            EventCategory::updateOrCreate(['slug' => $it['slug']], $it);
        }
    }
}

