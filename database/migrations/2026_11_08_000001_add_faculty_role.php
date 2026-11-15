<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Faculty accounts: professors / instructors can now create an account too.
 * - users.role gets a new value: 'faculty'
 * - users.employee_number: their ID (a faculty member has no student number)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_number', 30)->nullable()->unique()->after('student_number');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['student', 'faculty', 'admin', 'superadmin'])->default('student')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'faculty')->update(['role' => 'student']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['student', 'admin', 'superadmin'])->default('student')->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['employee_number']);
            $table->dropColumn('employee_number');
        });
    }
};
