<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Records when a vendor was first offered the guided tour, so it only appears on their first login on any device. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('tutorial_seen_at')->nullable()->after('last_activity_at');
        });
    }

    /** Removes the tutorial flag again. */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tutorial_seen_at');
        });
    }
};
