<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * distributors.status was created with only ['pending', 'approved', 'rejected']
     * (see add_status_to_distributors_table), but AdminModerationService::banDistributor()
     * sets it to 'banned' and EnsureDistributorVerified checks for that value — a value the
     * column has never actually allowed, on MySQL or SQLite. Unlike the orders.status drift
     * fixed separately, this one was missing everywhere, so both drivers need updating here.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE distributors MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'banned') NOT NULL DEFAULT 'pending'");

            return;
        }

        Schema::table('distributors', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected', 'banned'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe down migration: banned distributors would violate the narrower enum below.
        DB::table('distributors')->where('status', 'banned')->update(['status' => 'rejected']);

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE distributors MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");

            return;
        }

        Schema::table('distributors', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->change();
        });
    }
};
