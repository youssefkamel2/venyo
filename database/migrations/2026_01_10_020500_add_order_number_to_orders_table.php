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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_number', 20)->nullable()->after('id')->unique();
        });

        // Generate unique order numbers for existing orders
        $orders = \DB::table('orders')->whereNull('order_number')->get();
        foreach ($orders as $order) {
            $orderNumber = 'ORD-' . strtoupper(substr(md5($order->id . now()->timestamp), 0, 8));
            \DB::table('orders')->where('id', $order->id)->update(['order_number' => $orderNumber]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('order_number');
        });
    }
};
