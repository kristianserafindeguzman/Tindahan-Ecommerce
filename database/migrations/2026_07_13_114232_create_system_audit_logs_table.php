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
        Schema::create('system_audit_logs', function (Blueprint $table) {
            $table->increments('log_id'); // INT(11) Primary Key, Auto-Incrementing 
            $table->unsignedInteger('admin_id'); // Foreign Key referencing users.user_id 
            $table->string('action_performed', 255); // VARCHAR(255) for event description 
            $table->timestamp('created_at')->useCurrent(); // TIMESTAMP tracking execution date/time 

            // Foreign Key Constraint linking directly to custom users table structure 
            $table->foreign('admin_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade'); // Ensures database integrity if a user profile changes
        });
    }

    /**
     * Reverse migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_audit_logs');
    }
};