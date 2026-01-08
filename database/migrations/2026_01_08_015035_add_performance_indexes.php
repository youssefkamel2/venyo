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
        // Restaurants table indexes
        Schema::table('restaurants', function (Blueprint $table) {
            $table->index('slug');
            $table->index('is_active');
            $table->index('is_promoted');
            $table->index('is_profile_complete');
            $table->index(['is_active', 'is_profile_complete']);
            $table->index('area_id');
            $table->index('cuisine_type_id');
        });

        // Reservations table indexes
        Schema::table('reservations', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('restaurant_id');
            $table->index('status');
            $table->index('reservation_date');
            $table->index(['user_id', 'status']);
        });

        // Time slots table indexes
        Schema::table('time_slots', function (Blueprint $table) {
            $table->index('restaurant_id');
            $table->index('is_active');
        });

        // Reviews table indexes
        Schema::table('reviews', function (Blueprint $table) {
            $table->index('restaurant_id');
            $table->index('user_id');
            $table->index('is_visible');
            $table->index(['restaurant_id', 'is_visible']);
        });

        // Favorites table indexes
        Schema::table('favorites', function (Blueprint $table) {
            $table->index(['user_id', 'restaurant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['is_promoted']);
            $table->dropIndex(['is_profile_complete']);
            $table->dropIndex(['is_active', 'is_profile_complete']);
            $table->dropIndex(['area_id']);
            $table->dropIndex(['cuisine_type_id']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['restaurant_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['reservation_date']);
            $table->dropIndex(['user_id', 'status']);
        });

        Schema::table('time_slots', function (Blueprint $table) {
            $table->dropIndex(['restaurant_id']);
            $table->dropIndex(['is_active']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['restaurant_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['is_visible']);
            $table->dropIndex(['restaurant_id', 'is_visible']);
        });

        Schema::table('favorites', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'restaurant_id']);
        });
    }
};
