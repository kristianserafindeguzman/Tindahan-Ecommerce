<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The pickup time a consumer chose at checkout. Null means ASAP, which is also what every
     * order placed before this column existed was.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('scheduled_pickup_at')->nullable()->after('ready_for_pickup_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('scheduled_pickup_at');
        });
    }
};
