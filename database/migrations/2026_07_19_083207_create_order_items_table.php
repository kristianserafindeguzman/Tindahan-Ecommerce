<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->increments('order_item_id'); // PK matching project standards
            $table->unsignedInteger('order_id'); // FK linking to the orders table
            $table->unsignedInteger('inventory_id'); // FK linking to inventory table
            
            $table->integer('quantity');
            $table->decimal('subtotal', 8, 2); // Renamed to subtotal per manuscript

            // Foreign Key Constraints
            $table->foreign('order_id')
                  ->references('order_id')
                  ->on('orders')
                  ->onDelete('cascade');

            $table->foreign('inventory_id')
                  ->references('inventory_id')
                  ->on('inventory')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};