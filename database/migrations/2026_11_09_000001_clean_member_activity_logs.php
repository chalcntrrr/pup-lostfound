<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Activity Log now keeps ADMIN / SUPER ADMIN actions only. Remove the old entries made by
 * students, faculty and guests (log-ins, sign-ups, reports, claims) so the log stays relevant.
 */
return new class extends Migration
{
    public function up(): void
    {
        $officeIds = DB::table('users')->whereIn('role', ['admin', 'superadmin'])->pluck('id');

        DB::table('activity_logs')
            ->where(fn ($q) => $q->whereNull('user_id')->orWhereNotIn('user_id', $officeIds))
            ->delete();
    }

    public function down(): void
    {
        // deleted log lines can't be restored
    }
};
