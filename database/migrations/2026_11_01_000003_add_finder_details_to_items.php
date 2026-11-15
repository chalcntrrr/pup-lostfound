<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FOUND items are now logged only by the Lost and Found office when someone surrenders an item.
 * Like the Java "Record Finder Details / Keep Anonymous" option, the admin may record who
 * surrendered it. These details are visible to admins only, never on the public dashboard.
 * (is_anonymous = the finder chose "Keep Anonymous".)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('finder_name', 100)->nullable()->after('is_anonymous');
            $table->string('finder_student_id', 20)->nullable()->after('finder_name');
            $table->string('finder_contact', 100)->nullable()->after('finder_student_id');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['finder_name', 'finder_student_id', 'finder_contact']);
        });
    }
};
