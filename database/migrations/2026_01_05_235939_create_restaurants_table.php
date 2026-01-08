<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('restaurant_owners')->cascadeOnDelete();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('slug')->unique();
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->foreignId('sub_area_id')->constrained('sub_areas')->cascadeOnDelete();
            $table->foreignId('restaurant_type_id')->constrained('restaurant_types')->cascadeOnDelete();
            $table->foreignId('cuisine_type_id')->constrained('cuisine_types')->cascadeOnDelete();
            $table->string('address')->nullable();
            $table->string('google_maps_link')->nullable();
            $table->string('menu_link')->nullable();
            $table->string('phone')->nullable();
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->boolean('is_reservable')->default(true);
            $table->boolean('is_promoted')->default(false);
            $table->boolean('is_active')->default(false);
            $table->boolean('is_profile_complete')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
