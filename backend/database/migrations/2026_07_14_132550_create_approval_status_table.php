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
        Schema::create('approval_status', function (Blueprint $table) {

            $table->increments('approval_id');

            $table->unsignedInteger('store_id');

            $table->unsignedInteger('admin_id')->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected'
            ]);

            $table->text('rejection_reason')->nullable();

            $table->timestamp('reviewed_at')->nullable();

            $table->foreign('store_id')
                  ->references('store_id')
                  ->on('stores')
                  ->onDelete('cascade');

            $table->foreign('admin_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_status');
    }
};