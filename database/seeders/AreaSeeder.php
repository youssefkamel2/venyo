<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = [
            ['en' => 'Cairo', 'ar' => 'القاهرة'],
            ['en' => 'Giza', 'ar' => 'الجيزة'],
            ['en' => 'Alexandria', 'ar' => 'الإسكندرية'],
        ];

        foreach ($areas as $area) {
            Area::create([
                'name_en' => $area['en'],
                'name_ar' => $area['ar'],
                'slug' => Str::slug($area['en']),
                'is_active' => true,
            ]);
        }
    }
}
