<?php

namespace Database\Seeders;

use App\Models\RestaurantType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RestaurantTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['en' => 'Restaurant', 'ar' => 'مطعم'],
            ['en' => 'Cafe', 'ar' => 'كافيه'],
        ];

        foreach ($types as $type) {
            RestaurantType::create([
                'name_en' => $type['en'],
                'name_ar' => $type['ar'],
                'slug' => Str::slug($type['en']),
            ]);
        }
    }
}
