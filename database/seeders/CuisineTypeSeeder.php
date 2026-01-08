<?php

namespace Database\Seeders;

use App\Models\CuisineType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CuisineTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cuisines = [
            ['en' => 'Oriental', 'ar' => 'شرقي'],
            ['en' => 'Italian', 'ar' => 'إيطالي'],
            ['en' => 'Lebanese', 'ar' => 'لبناني'],
            ['en' => 'Burger', 'ar' => 'برجر'],
            ['en' => 'Sushi', 'ar' => 'سوشي'],
            ['en' => 'Egyptian', 'ar' => 'مصري'],
        ];

        foreach ($cuisines as $cuisine) {
            CuisineType::create([
                'name_en' => $cuisine['en'],
                'name_ar' => $cuisine['ar'],
                'slug' => Str::slug($cuisine['en']),
                'is_active' => true,
            ]);
        }
    }
}
