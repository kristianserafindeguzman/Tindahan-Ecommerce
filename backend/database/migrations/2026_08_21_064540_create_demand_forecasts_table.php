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
        Schema::create('demand_forecasts', function (Blueprint $table) {
            $table->increments('forecast_id');
            $table->unsignedInteger('store_id');
            $table->unsignedInteger('inventory_id');
            $table->date('forecast_date');
            $table->decimal('predicted_quantity', 10, 2);
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();

            $table->foreign('store_id')->references('store_id')->on('stores')->onDelete('cascade');
            $table->foreign('inventory_id')->references('inventory_id')->on('inventory')->onDelete('cascade');
            
            // Unique constraint to prevent duplicate forecasts per store/product/date
            // This satisfies Checkpoint 4 safeguard #2
            $table->unique(['store_id', 'inventory_id', 'forecast_date'], 'unique_forecast');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demand_forecasts');
    }
};
