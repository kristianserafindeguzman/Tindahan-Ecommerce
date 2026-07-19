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
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('order_id'); // PK matching project standards
            $table->unsignedInteger('consumer_id'); // FK to users
            $table->unsignedInteger('store_id'); // FK to stores
            
            $table->decimal('total_amount', 10, 2);
            $table->string('status', 50)->default('pending'); // Handles 'completed' filtering the ML view
            
            $table->timestamps(); // Generates created_at and updated_at

            // Foreign Key Constraints
            $table->foreign('consumer_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('store_id')
                  ->references('store_id')
                  ->on('stores')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};