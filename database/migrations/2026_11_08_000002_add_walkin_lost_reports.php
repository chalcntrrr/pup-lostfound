<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Walk-in LOST reports: an admin can post a lost item for someone who has no account
 * (the owner details are typed in by the admin).
 * - items: owner_name / owner_id_number / owner_contact (filled only for walk-in lost reports;
 *          items.user_id is then the admin who logged it)
 * - claims: when a found item is released to a walk-in owner there is no user account, so the
 *           claim keeps the person's details itself (claims.user_id becomes optional).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('owner_name', 100)->nullable()->after('finder_contact');
            $table->string('owner_id_number', 30)->nullable()->after('owner_name');
            $table->string('owner_contact', 100)->nullable()->after('owner_id_number');
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('claimant_name', 100)->nullable()->after('user_id');
            $table->string('claimant_id_number', 30)->nullable()->after('claimant_name');
        });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn(['claimant_name', 'claimant_id_number']);
        });

        // Walk-in claims have no account, so they can't exist in the old structure.
        \Illuminate\Support\Facades\DB::table('claims')->whereNull('user_id')->delete();

        Schema::table('claims', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['owner_name', 'owner_id_number', 'owner_contact']);
        });
    }
};
