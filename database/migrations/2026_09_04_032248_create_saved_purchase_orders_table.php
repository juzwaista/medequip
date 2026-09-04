<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label')->default('My Purchase Order'); // e.g. "St. Jude Hospital PO"
            $table->string('company_name');
            $table->string('authorized_signatory')->nullable(); // Name of the person signing the PO
            $table->string('contact_number')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('tin')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_purchase_orders');
    }
};
