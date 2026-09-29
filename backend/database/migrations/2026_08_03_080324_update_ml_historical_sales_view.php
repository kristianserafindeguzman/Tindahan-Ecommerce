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
                transaction_date,
                store_id,
                inventory_id,
                SUM(quantity) as total_daily_quantity,
                CASE
                    WHEN MONTH(transaction_date) IN (3, 4, 5) THEN 'Summer'
                    WHEN MONTH(transaction_date) IN (6, 7, 8, 9, 10) THEN 'Rainy'
                    WHEN MONTH(transaction_date) IN (11, 12, 1, 2) THEN 'Amihan'
                    ELSE 'Unknown'
                END as season_category,
                CASE
                    WHEN MONTH(transaction_date) = 12 AND DAY(transaction_date) >= 16 THEN 'Christmas'
                    WHEN MONTH(transaction_date) = 12 AND DAY(transaction_date) = 31 THEN 'New Year'
                    WHEN MONTH(transaction_date) = 1 AND DAY(transaction_date) <= 2 THEN 'New Year'
                    WHEN MONTH(transaction_date) = 2 AND DAY(transaction_date) BETWEEN 13 AND 15 THEN 'Valentines'
                    WHEN MONTH(transaction_date) = 3 AND DAY(transaction_date) >= 25 THEN 'Holy Week'
                    WHEN MONTH(transaction_date) = 4 AND DAY(transaction_date) <= 10 THEN 'Holy Week'
                    WHEN MONTH(transaction_date) = 5 THEN 'Fiesta'
                    WHEN MONTH(transaction_date) = 8 OR (MONTH(transaction_date) = 9 AND DAY(transaction_date) <= 15) THEN 'Back to School'
                    WHEN MONTH(transaction_date) = 10 AND DAY(transaction_date) >= 30 THEN 'Undas'
                    WHEN MONTH(transaction_date) = 11 AND DAY(transaction_date) <= 2 THEN 'Undas'
                    ELSE 'None'
                END as holiday_event
            FROM (
                SELECT 
                    DATE(o.updated_at) as transaction_date,
                    o.store_id,
                    oi.inventory_id,
                    oi.quantity
                FROM orders o
                JOIN order_items oi ON o.order_id = oi.order_id
                WHERE o.status = 'picked_up'
            ) as raw_sales
            GROUP BY 
                transaction_date,
                store_id,
                inventory_id
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
