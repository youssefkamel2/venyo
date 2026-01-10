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
        Schema::table('restaurants', function (Blueprint $table) {
            $table->boolean('auto_accept')->default(false)->after('is_reservable');
            $table->enum('seating_options', ['indoor', 'outdoor', 'both'])->default('both')->after('auto_accept');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->text('dietary_preferences')->nullable()->after('special_request');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['auto_accept', 'seating_options']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('dietary_preferences');
        });
    }
};
