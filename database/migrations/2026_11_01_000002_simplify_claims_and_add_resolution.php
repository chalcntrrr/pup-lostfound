<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - claims: drop "number of claimants" and the student-uploaded ID photo.
 *   "ID Presented" is now filled in by the admin when approving (checked at the office).
 * - items: remember how a report was resolved (like the Java RESOLVE dialog):
 *   matched with an existing found item, or resolved outside the system.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn(['claimant_count', 'id_image_path']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->enum('resolution', ['matched', 'outside'])->nullable()->after('status');
            $table->foreignId('matched_item_id')->nullable()->after('resolution')
                ->constrained('items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('matched_item_id');
            $table->dropColumn('resolution');
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->string('id_image_path')->nullable()->after('id_presented');
            $table->enum('claimant_count', ['one', 'multiple'])->default('one')->after('id_image_path');
        });
    }
};
