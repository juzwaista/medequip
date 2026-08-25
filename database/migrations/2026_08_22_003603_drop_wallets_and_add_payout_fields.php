<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Clean up old data to prevent foreign key errors
        DB::table('withdrawal_requests')->truncate();
        if (Schema::hasColumn('payments', 'payment_method')) {
            DB::table('payments')->where('payment_method', 'wallet')->update(['payment_method' => 'cash']);
        }

        // 2. Modify withdrawal_requests table
        if (Schema::hasColumn('withdrawal_requests', 'wallet_id')) {
            Schema::table('withdrawal_requests', function (Blueprint $table) {
                $table->dropForeign(['wallet_id']);
                $table->dropColumn('wallet_id');
                $table->foreignId('distributor_id')->after('id')->constrained()->cascadeOnDelete();
            });
        }

        // 3. Drop wallets and wallet_transactions
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');

        // 4. Add payout fields to distributors
        if (!Schema::hasColumn('distributors', 'payout_bank')) {
            Schema::table('distributors', function (Blueprint $table) {
                $table->string('payout_bank')->nullable()->after('status');
                $table->string('payout_account_name')->nullable()->after('payout_bank');
                $table->string('payout_account_number')->nullable()->after('payout_account_name');
            });
        }

        // 5. Add payout fields to couriers
        if (!Schema::hasColumn('couriers', 'payout_bank')) {
            Schema::table('couriers', function (Blueprint $table) {
                $table->string('payout_bank')->nullable()->after('status');
                $table->string('payout_account_name')->nullable()->after('payout_bank');
                $table->string('payout_account_number')->nullable()->after('payout_account_name');
            });
        }

        // 6. Modify payments table
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'seller_wallet_credited_at')) {
                $table->dropColumn('seller_wallet_credited_at');
            }
            if (!Schema::hasColumn('payments', 'seller_payout_cleared_at')) {
                $table->timestamp('seller_payout_cleared_at')->nullable()->after('released_at');
            }
        });

        // 7. Remove wallet from payment_method ENUM
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash','bank_transfer','gcash','paymaya','paymongo','card','grab_pay') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back wallet to ENUM
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('cash','wallet','bank_transfer','gcash','paymaya','paymongo','card','grab_pay') NOT NULL");
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('seller_payout_cleared_at');
            $table->timestamp('seller_wallet_credited_at')->nullable()->after('released_at');
        });

        Schema::table('couriers', function (Blueprint $table) {
            $table->dropColumn(['payout_bank', 'payout_account_name', 'payout_account_number']);
        });

        Schema::table('distributors', function (Blueprint $table) {
            $table->dropColumn(['payout_bank', 'payout_account_name', 'payout_account_number']);
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('description');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
        });

        DB::table('withdrawal_requests')->truncate();
        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->dropForeign(['distributor_id']);
            $table->dropColumn('distributor_id');
            $table->foreignId('wallet_id')->after('id')->constrained()->cascadeOnDelete();
        });
    }
};
