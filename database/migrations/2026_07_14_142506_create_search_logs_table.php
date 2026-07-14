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
        Schema::create('search_logs', function (Blueprint $table) {
            $table->increments('log_id'); // PK

            $table->unsignedInteger('consumer_id'); // FK
            $table->unsignedInteger('category_id'); // FK

            $table->string('search_query', 255);

            $table->decimal('search_lat', 10, 8);
            $table->decimal('search_lng', 11, 8);

            $table->timestamp('searched_at')->useCurrent();

            // Foreign Key Constraints
            $table->foreign('consumer_id')
                  ->references('user_id')
                  ->on('users')
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
        Schema::dropIfExists('search_logs');
    }
};