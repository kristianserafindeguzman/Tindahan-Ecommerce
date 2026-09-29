<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Adds a pending status for sign-ups that have not verified their mobile number, so they are no longer mixed up with accounts an admin set inactive. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_status', ['active', 'inactive', 'suspended', 'deleted', 'pending'])->default('active')->change();
        });

        // Accounts an admin set inactive, read from the audit log so they are never mistaken for unfinished sign-ups.
        $adminSet = DB::table('system_audit_logs')
            ->where('action_performed', 'like', "Updated % status to 'inactive'")
            ->pluck('action_performed')
            ->map(fn ($text) => preg_match('/^Updated (?:consumer|vendor) (\d+) status/', $text, $m) ? (int) $m[1] : null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        // An inactive consumer with a sign-up code on record but none ever verified never finished signing up, so it becomes pending.
        DB::table('users')
            ->where('role', 'Consumer')
            ->where('account_status', 'inactive')
            ->whereNull('suspension_message')
            ->whereNotIn('user_id', $adminSet)
            ->whereExists(fn ($q) => $q->select(DB::raw(1))
                ->from('otp_codes')
                ->whereColumn('otp_codes.phone_number', 'users.phone_number')
                ->where('otp_codes.type', 'registration'))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                ->from('otp_codes')
                ->whereColumn('otp_codes.phone_number', 'users.phone_number')
                ->where('otp_codes.type', 'registration')
                ->whereNotNull('otp_codes.verified_at'))
            ->update(['account_status' => 'pending']);
    }

    /** Folds pending back into inactive before the column loses the value, which is how these accounts were stored before. */
    public function down(): void
    {
        DB::table('users')->where('account_status', 'pending')->update(['account_status' => 'inactive']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_status', ['active', 'inactive', 'suspended', 'deleted'])->default('active')->change();
        });
    }
};
