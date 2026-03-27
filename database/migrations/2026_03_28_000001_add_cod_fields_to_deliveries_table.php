<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->decimal('cod_amount', 10, 2)->nullable()->after('courier_payout_status')
                ->comment('Cash collected from customer for COD orders');
            $table->timestamp('cod_collected_at')->nullable()->after('cod_amount')
                ->comment('When courier collected cash from customer');
            $table->timestamp('cod_remitted_at')->nullable()->after('cod_collected_at')
                ->comment('When cash was remitted/credited to distributor');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn(['cod_amount', 'cod_collected_at', 'cod_remitted_at']);
        });
    }
};
