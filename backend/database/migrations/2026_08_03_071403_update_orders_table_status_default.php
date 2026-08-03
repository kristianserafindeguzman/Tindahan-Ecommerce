<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL, simply alter the column default to 'placed'
        DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(50) DEFAULT 'placed'");
        // And update existing 'pending' to 'placed'
        DB::table('orders')->where('status', 'pending')->update(['status' => 'placed']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");
    }
};
