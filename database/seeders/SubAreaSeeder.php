<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\SubArea;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubAreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cairo = Area::where('slug', 'cairo')->first();
        $giza = Area::where('slug', 'giza')->first();

        if ($cairo) {
            $subAreas = [
                ['en' => 'Maadi', 'ar' => 'المعادي'],
                ['en' => 'New Cairo', 'ar' => 'القاهرة الجديدة'],
                ['en' => 'Nasr City', 'ar' => 'مدينة نصر'],
                ['en' => 'Zamalek', 'ar' => 'الزمالك'],
            ];

            foreach ($subAreas as $subArea) {
                SubArea::create([
                    'area_id' => $cairo->id,
                    'name_en' => $subArea['en'],
                    'name_ar' => $subArea['ar'],
                    'slug' => Str::slug($subArea['en']),
                    'is_active' => true,
                ]);
            }
        }

        if ($giza) {
            $subAreas = [
                ['en' => 'Sheikh Zayed', 'ar' => 'الشيخ زايد'],
                ['en' => '6th of October', 'ar' => '6 أكتوبر'],
                ['en' => 'Dokki', 'ar' => 'الدقي'],
            ];

            foreach ($subAreas as $subArea) {
                SubArea::create([
                    'area_id' => $giza->id,
                    'name_en' => $subArea['en'],
                    'name_ar' => $subArea['ar'],
                    'slug' => Str::slug($subArea['en']),
                    'is_active' => true,
                ]);
            }
        }
    }
}
