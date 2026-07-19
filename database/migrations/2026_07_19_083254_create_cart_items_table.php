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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->increments('cart_id'); // PK matching project standards
            $table->unsignedInteger('consumer_id'); // FK linking to the users table
            $table->unsignedInteger('inventory_id'); // FK linking to Lawrence's inventory table
            
            $table->integer('quantity')->default(1);
            $table->timestamps(); // Keeps track of when items were added/updated

            // Foreign Key Constraints
            $table->foreign('consumer_id')
                  ->references('user_id')
                  ->on('users')
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
        Schema::dropIfExists('cart_items');
    }
};