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
        Schema::table('stores', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('store_name');
        });

        // Backfill existing stores with slugs
        $stores = \Illuminate\Support\Facades\DB::table('stores')->get();
        foreach ($stores as $store) {
            $baseSlug = \Illuminate\Support\Str::slug($store->store_name);
            $slug = $baseSlug;
            $count = 1;
            while (\Illuminate\Support\Facades\DB::table('stores')->where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $count;
                $count++;
            }
            \Illuminate\Support\Facades\DB::table('stores')->where('store_id', $store->store_id)->update(['slug' => $slug]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
