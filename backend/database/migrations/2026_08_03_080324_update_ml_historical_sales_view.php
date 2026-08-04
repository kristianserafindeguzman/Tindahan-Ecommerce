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
        DB::statement("
            CREATE OR REPLACE VIEW ml_historical_sales_view AS
            SELECT 
                DATE(o.updated_at) as transaction_date,
                o.store_id,
                oi.inventory_id,
                SUM(oi.quantity) as total_daily_quantity,
                CASE
                    WHEN MONTH(o.updated_at) IN (3, 4, 5) THEN 'Summer'
                    WHEN MONTH(o.updated_at) IN (6, 7, 8, 9, 10) THEN 'Rainy'
                    WHEN MONTH(o.updated_at) IN (11, 12, 1, 2) THEN 'Amihan'
                    ELSE 'Unknown'
                END as season_category,
                CASE
                    WHEN MONTH(o.updated_at) = 12 AND DAY(o.updated_at) >= 16 THEN 'Christmas'
                    WHEN MONTH(o.updated_at) = 12 AND DAY(o.updated_at) = 31 THEN 'New Year'
                    WHEN MONTH(o.updated_at) = 1 AND DAY(o.updated_at) <= 2 THEN 'New Year'
                    WHEN MONTH(o.updated_at) = 2 AND DAY(o.updated_at) BETWEEN 13 AND 15 THEN 'Valentines'
                    WHEN MONTH(o.updated_at) = 3 AND DAY(o.updated_at) >= 25 THEN 'Holy Week'
                    WHEN MONTH(o.updated_at) = 4 AND DAY(o.updated_at) <= 10 THEN 'Holy Week'
                    WHEN MONTH(o.updated_at) = 5 THEN 'Fiesta'
                    WHEN MONTH(o.updated_at) = 8 OR (MONTH(o.updated_at) = 9 AND DAY(o.updated_at) <= 15) THEN 'Back to School'
                    WHEN MONTH(o.updated_at) = 10 AND DAY(o.updated_at) >= 30 THEN 'Undas'
                    WHEN MONTH(o.updated_at) = 11 AND DAY(o.updated_at) <= 2 THEN 'Undas'
                    ELSE 'None'
                END as holiday_event
            FROM orders o
            JOIN order_items oi ON o.order_id = oi.order_id
            WHERE o.status = 'picked_up'
            GROUP BY 
                DATE(o.updated_at),
                o.store_id,
                oi.inventory_id,
                season_category,
                holiday_event
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS ml_historical_sales_view");
    }
};
