<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Clean the slate if the virtual view exists from a prior runtime block
        DB::statement("DROP VIEW IF EXISTS ml_historical_sales_view");

        // 2. Active Virtual Pipeline Layer mapping out the transactional variables for model extraction
        DB::statement("
            CREATE VIEW ml_historical_sales_view AS
            SELECT 
                DATE(o.created_at) AS transaction_date,
                o.store_id AS store_id,
                oi.inventory_id AS inventory_id,
                SUM(oi.quantity) AS total_daily_quantity,
                'Summer' AS season_category,
                'None' AS holiday_event
            FROM orders o
            JOIN order_items oi ON o.order_id = oi.order_id
            WHERE o.status = 'completed'
            GROUP BY DATE(o.created_at), o.store_id, oi.inventory_id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safely tear down the virtual pipeline layout on rollback routines
        DB::statement("DROP VIEW IF EXISTS ml_historical_sales_view");
    }
};