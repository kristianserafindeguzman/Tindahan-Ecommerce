<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Adds the OTP type used when a signed-in consumer or vendor changes their password. */
    public function up(): void
    {
        DB::statement("ALTER TABLE otp_codes MODIFY COLUMN type ENUM('registration', 'password_reset', 'password_change') NOT NULL");
    }

    public function down(): void
    {
        // Rows of the new type would not fit the old column, so they go first.
        DB::table('otp_codes')->where('type', 'password_change')->delete();

        DB::statement("ALTER TABLE otp_codes MODIFY COLUMN type ENUM('registration', 'password_reset') NOT NULL");
    }
};
