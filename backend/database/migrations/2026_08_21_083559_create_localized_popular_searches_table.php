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
        Schema::create('localized_popular_searches', function (Blueprint $table) {
            $table->id('popular_search_id');
            $table->decimal('lat_grid', 8, 2);
            $table->decimal('lng_grid', 8, 2);
            $table->string('search_query');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->integer('search_count')->default(0);
            $table->timestamp('generated_at')->useCurrent();

            $table->unique(['lat_grid', 'lng_grid', 'search_query'], 'unique_grid_query');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('localized_popular_searches');
    }
};
