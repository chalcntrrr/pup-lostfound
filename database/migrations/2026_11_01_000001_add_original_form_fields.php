<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extra fields from the original JavaFX forms:
 * - items: time lost/found, "Keep Anonymous" for found reports
 * - claims: ID presented, uploaded ID photo, number of claimants
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->time('time_happened')->nullable()->after('date_happened');
            $table->boolean('is_anonymous')->default(false)->after('image_path');
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->string('id_presented', 100)->nullable()->after('contact_number');
            $table->string('id_image_path')->nullable()->after('id_presented');
            $table->enum('claimant_count', ['one', 'multiple'])->default('one')->after('id_image_path');
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->string('contact_number', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['time_happened', 'is_anonymous']);
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn(['id_presented', 'id_image_path', 'claimant_count']);
        });
    }
};
