<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter enum to include 'completed' state
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE orders MODIFY status ENUM('pending', 'approved', 'processing', 'packed', 'shipped', 'delivered', 'cancelled', 'rejected', 'completed') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert enum back to original
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE orders MODIFY status ENUM('pending', 'approved', 'processing', 'packed', 'shipped', 'delivered', 'cancelled', 'rejected') DEFAULT 'pending'");
    }
};
