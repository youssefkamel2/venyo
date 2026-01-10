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
        // Create the parent orders table
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamps();
        });

        // Add order_id to order_items and remove reservation_id
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('id')->constrained('orders')->cascadeOnDelete();
        });

        // Migrate existing data: Create an order for each reservation that has items
        $reservationsWithItems = \DB::table('order_items')
            ->select('reservation_id')
            ->distinct()
            ->get();

        foreach ($reservationsWithItems as $row) {
            // Calculate total
            $total = \DB::table('order_items')
                ->where('reservation_id', $row->reservation_id)
                ->selectRaw('SUM(price * quantity) as total')
                ->value('total');

            // Create the order
            $orderId = \DB::table('orders')->insertGetId([
                'reservation_id' => $row->reservation_id,
                'total' => $total ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update order_items to point to this new order
            \DB::table('order_items')
                ->where('reservation_id', $row->reservation_id)
                ->update(['order_id' => $orderId]);
        }

        // Now drop the reservation_id column from order_items
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['reservation_id']);
            $table->dropColumn('reservation_id');
        });

        // Make order_id required
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add reservation_id to order_items
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('reservation_id')->nullable()->after('id')->constrained('reservations')->cascadeOnDelete();
        });

        // Migrate data back
        $orders = \DB::table('orders')->get();
        foreach ($orders as $order) {
            \DB::table('order_items')
                ->where('order_id', $order->id)
                ->update(['reservation_id' => $order->reservation_id]);
        }

        // Drop order_id
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
        });

        // Drop orders table
        Schema::dropIfExists('orders');
    }
};
