<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run migrations.
     */
    public function up(): void
    {
        // 1. Users Table (based from data dictionary)
        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id'); // INT(11) Primary Key, Auto-Incrementing
            $table->enum('role', ['owner', 'consumer']); // ENUM constraint for system access levels
            $table->string('full_name', 100); // VARCHAR(100) for profile identification
            $table->string('phone_number', 15); // VARCHAR(15) for checkout/pickup contact
            $table->string('email', 100)->unique(); // VARCHAR(100) and Unique constraint
            $table->string('password_hash', 255); // VARCHAR(255) for secure cryptographic hashing
            $table->timestamp('created_at')->useCurrent(); // TIMESTAMP tracking registration dates
        });

        // 2. For Native Laravel Authentication Security
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // 3. Retained for Native State Management & Active Sessions
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            // Pointed to custom unsigned integer user_id foreign key constraint
            $table->unsignedInteger('user_id')->nullable()->index(); 
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};