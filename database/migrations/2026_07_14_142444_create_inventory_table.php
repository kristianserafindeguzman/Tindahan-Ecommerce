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
        Schema::create('inventory', function (Blueprint $table) {
            $table->increments('inventory_id'); // PK

            $table->unsignedInteger('store_id');      // FK
            $table->unsignedInteger('category_id');   // FK

            $table->string('product_name', 100);
            $table->decimal('price', 8, 2);
            $table->integer('stock_quantity');
            $table->enum('status', ['active', 'archived']);

            // Foreign Key Constraints
            $table->foreign('store_id')
                  ->references('store_id')
                  ->on('stores')
                  ->onDelete('cascade');

            $table->foreign('category_id')
                  ->references('category_id')
                  ->on('categories')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};