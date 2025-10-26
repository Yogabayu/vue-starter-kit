<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['slug' => 'toilet', 'name' => 'Toilet', 'icon' => 'mdi:toilet'],
            ['slug' => 'parking', 'name' => 'Parking', 'icon' => 'mdi:parking'],
            ['slug' => 'wifi', 'name' => 'Wi-Fi', 'icon' => 'mdi:wifi'],
        ];
        foreach ($items as $it) {
            Facility::updateOrCreate(['slug' => $it['slug']], $it);
        }
    }
}

