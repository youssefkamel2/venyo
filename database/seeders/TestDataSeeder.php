<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\CuisineType;
use App\Models\Restaurant;
use App\Models\RestaurantOwner;
use App\Models\RestaurantType;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Test Customer
        $customer = User::updateOrCreate(
            ['email' => 'customer@test.com'],
            [
                'name' => 'John Customer',
                'phone' => '01123456789',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        // 2. Create Test Restaurant Owner
        $owner = RestaurantOwner::updateOrCreate(
            ['email' => 'owner@test.com'],
            [
                'name' => 'Sara Owner',
                'phone' => '01012345678',
                'password' => Hash::make('password'),
            ]
        );

        // 3. Ensure we have basic lookups (Areas/Cuisines)
        $area = Area::first() ?? Area::create(['name_en' => 'Cairo', 'name_ar' => 'القاهرة', 'slug' => 'cairo']);
        $subArea = \App\Models\SubArea::first() ?? \App\Models\SubArea::create([
            'area_id' => $area->id,
            'name_en' => 'Maadi',
            'name_ar' => 'المعادي',
            'slug' => 'maadi',
            'is_active' => true
        ]);
        $cuisine = CuisineType::first() ?? CuisineType::create(['name_en' => 'Italian', 'name_ar' => 'إيطالي', 'slug' => 'italian']);
        $type = RestaurantType::first() ?? RestaurantType::create(['name_en' => 'Fine Dining', 'name_ar' => 'فاين داينينج', 'slug' => 'fine-dining']);

        // 4. Create Test Restaurant
        $restaurant = Restaurant::updateOrCreate(
            ['owner_id' => $owner->id],
            [
                'name_en' => 'The Venyo Grill',
                'name_ar' => 'فينيو جريل',
                'slug' => 'venyo-grill',
                'description_en' => 'Amazing fine dining experience with real-time booking.',
                'description_ar' => 'تجربة طعام فاخرة مع حجز فوري.',
                'area_id' => $area->id,
                'sub_area_id' => $subArea->id,
                'cuisine_type_id' => $cuisine->id,
                'restaurant_type_id' => $type->id,
                'address' => '123 Test St, Cairo',
                'google_maps_link' => 'https://maps.app.goo.gl/3fmx2Z6E2zK2U5P97',
                'menu_link' => 'https://venyo.app/menu/the-venyo-grill',
                'phone' => '021234567',
                'opening_time' => '12:00:00',
                'closing_time' => '23:00:00',
                'is_reservable' => true,
                'is_active' => true,
                'is_profile_complete' => true,
            ]
        );

        // 5. Create Time Slots (Daily Recurring)
        $times = ['12:30', '14:00', '16:30', '18:00', '19:30', '21:00'];

        foreach ($times as $time) {
            TimeSlot::updateOrCreate(
                [
                    'restaurant_id' => $restaurant->id,
                    'start_time' => $time,
                ],
                [
                    'end_time' => date('H:i', strtotime($time . ' + 1 hour 30 minutes')),
                    'tables_count' => 1, // 1 tables per slot
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Test data seeded successfully!');
        $this->command->info('Customer: customer@test.com / password');
        $this->command->info('Owner: owner@test.com / password');
        $this->command->info('Restaurant Slug: venyo-grill');
    }
}
