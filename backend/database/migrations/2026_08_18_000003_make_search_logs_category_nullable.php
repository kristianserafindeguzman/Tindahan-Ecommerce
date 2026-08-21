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
        Schema::table('search_logs', function (Blueprint $table) {
            // Drop existing foreign key
            $table->dropForeign(['category_id']);
            
            // Make column nullable
            $table->unsignedInteger('category_id')->nullable()->change();
            
            // Re-add foreign key with set null on delete
            $table->foreign('category_id')
                  ->references('category_id')
                  ->on('categories')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_logs', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->unsignedInteger('category_id')->nullable(false)->change();
            $table->foreign('category_id')
                  ->references('category_id')
                  ->on('categories');
        });
    }
};
