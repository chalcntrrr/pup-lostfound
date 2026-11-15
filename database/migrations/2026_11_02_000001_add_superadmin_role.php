<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the SUPER ADMIN role (max 2). Super admins do everything an admin does, plus they are
 * the only ones who can add admin accounts, delete accounts and reset passwords.
 * On an existing database the first admin account becomes the super admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['student', 'admin', 'superadmin'])->default('student')->change();
        });

        if (! DB::table('users')->where('role', 'superadmin')->exists()) {
            $first = DB::table('users')->where('role', 'admin')->orderBy('id')->value('id');
            if ($first) {
                DB::table('users')->where('id', $first)->update(['role' => 'superadmin']);
            }
        }
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'superadmin')->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['student', 'admin'])->default('student')->change();
        });
    }
};
