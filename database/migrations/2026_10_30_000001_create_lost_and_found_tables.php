<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Core tables of the PUP Lost and Found system.
 * Based on the old Java app's tables (category, item_reports, claim, activity_logs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->timestamps();
        });

        // One table for both lost and found reports ("type" tells them apart).
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();      // who posted it
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['lost', 'found']);
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('location', 100);                                       // where it was lost / found
            $table->date('date_happened');                                         // date lost / date found
            $table->string('image_path')->nullable();
            $table->enum('status', ['open', 'claimed', 'resolved'])->default('open');
            $table->timestamps();

            $table->index(['type', 'status']);
        });

        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();      // the student claiming
            $table->text('proof_details');                                         // identifying marks, contents, etc.
            $table->string('proof_image_path')->nullable();
            $table->string('contact_number', 20);
            $table->date('date_lost')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('claims');
        Schema::dropIfExists('items');
        Schema::dropIfExists('categories');
    }
};
