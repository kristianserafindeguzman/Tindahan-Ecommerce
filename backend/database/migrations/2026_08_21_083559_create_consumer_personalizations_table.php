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
        Schema::create('consumer_personalizations', function (Blueprint $table) {
            $table->id('personalization_id');
            $table->unsignedBigInteger('consumer_id');
            $table->unsignedBigInteger('category_id');
            $table->decimal('predicted_future_searches', 10, 4)->default(0);
            $table->timestamp('generated_at')->useCurrent();
            
            $table->unique(['consumer_id', 'category_id'], 'unique_consumer_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumer_personalizations');
    }
};
