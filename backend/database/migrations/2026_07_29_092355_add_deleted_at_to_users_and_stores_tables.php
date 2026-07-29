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
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_picture')->nullable()->after('full_name');
            $table->softDeletes();
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->json('operating_days')->nullable()->after('closing_time');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profile_picture');
            $table->dropSoftDeletes();
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('operating_days');
            $table->dropSoftDeletes();
        });
    }
};
