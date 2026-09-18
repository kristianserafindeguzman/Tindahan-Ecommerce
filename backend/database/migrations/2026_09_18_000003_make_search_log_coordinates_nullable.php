<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow a search to be logged without coordinates.
     *
     * 2026_08_15_182012_make_search_log_fields_required made these NOT NULL, which meant a
     * consumer with no detected or saved address had their searches dropped before they were
     * ever written. Search history and the category-level personalization built on it do not
     * need coordinates; only the localized popular-search path does, and that path already
     * filters rows by coordinate itself.
     *
     * Existing rows keep their coordinates; this only relaxes the constraint.
     */
    public function up(): void
    {
        Schema::table('search_logs', function (Blueprint $table) {
            $table->decimal('search_lat', 10, 8)->nullable()->change();
            $table->decimal('search_lng', 11, 8)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Rows logged without coordinates cannot satisfy a NOT NULL constraint, so they are
        // removed rather than backfilled with invented locations.
        \Illuminate\Support\Facades\DB::table('search_logs')
            ->whereNull('search_lat')
            ->orWhereNull('search_lng')
            ->delete();

        Schema::table('search_logs', function (Blueprint $table) {
            $table->decimal('search_lat', 10, 8)->nullable(false)->change();
            $table->decimal('search_lng', 11, 8)->nullable(false)->change();
        });
    }
};
