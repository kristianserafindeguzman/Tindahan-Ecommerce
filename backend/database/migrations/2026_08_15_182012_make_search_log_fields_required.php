<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('search_logs', function (Blueprint $table) {
            $table->unsignedInteger('category_id')->nullable(false)->change();
            $table->decimal('search_lat', 10, 8)->nullable(false)->change();
            $table->decimal('search_lng', 11, 8)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('search_logs', function (Blueprint $table) {
            $table->unsignedInteger('category_id')->nullable()->change();
            $table->decimal('search_lat', 10, 8)->nullable()->change();
            $table->decimal('search_lng', 11, 8)->nullable()->change();
        });
    }
};