<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $restaurants = Restaurant::all();

        if ($restaurants->isEmpty()) {
            return;
        }

        foreach ($restaurants as $restaurant) {
            // Create 3 categories for each restaurant
            $categories = [
                ['name_en' => 'Appetizers', 'name_ar' => 'مقبلات'],
                ['name_en' => 'Main Courses', 'name_ar' => 'الأطباق الرئيسية'],
                ['name_en' => 'Drinks', 'name_ar' => 'مشروبات'],
            ];

            foreach ($categories as $index => $catData) {
                $category = MenuCategory::create([
                    'restaurant_id' => $restaurant->id,
                    'name_en' => $catData['name_en'],
                    'name_ar' => $catData['name_ar'],
                    'sort_order' => $index,
                ]);

                // Add sample items to each category
                if ($catData['name_en'] === 'Appetizers') {
                    $this->createMenuItem($category, 'Special House Salad', 'سلطة البيت الخاصة', 85, 'Starters');
                    $this->createMenuItem($category, 'Crispy Calamari', 'كالاماري مقرمش', 120, 'Starters');
                } elseif ($catData['name_en'] === 'Main Courses') {
                    $this->createMenuItem($category, 'Grilled Ribeye Steak', 'ستيك ريب آي مشوي', 450, 'Mains');
                    $this->createMenuItem($category, 'Seafood Pasta', 'باستا فواكه البحر', 280, 'Mains');
                } else {
                    $this->createMenuItem($category, 'Fresh Orange Juice', 'عصير برتقال فريش', 45, 'Beverages');
                    $this->createMenuItem($category, 'Classic Mojito', 'موهيتو كلاسيك', 65, 'Beverages');
                }
            }
        }
    }

    private function createMenuItem($category, $nameEn, $nameAr, $price, $course)
    {
        return MenuItem::create([
            'menu_category_id' => $category->id,
            'name_en' => $nameEn,
            'name_ar' => $nameAr,
            'description_en' => "Delicious $nameEn prepared with fresh ingredients.",
            'description_ar' => "وصف شهي لـ $nameAr محضر بمكونات طازجة.",
            'price' => $price,
            'course' => $course,
            'is_available' => true,
        ]);
    }
}
