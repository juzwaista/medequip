<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Approving a distributor application only set status = 'approved' and never is_verified, so
     * every shop approved through the admin UI stayed unverified (no branches, no distributor
     * wholesale access, hidden from the public shop filter). Approval now sets the flag; this
     * brings existing approved shops in line. Rejected/banned/pending shops are left as they are.
     */
    public function up(): void
    {
        DB::table('distributors')
            ->where('status', 'approved')
            ->where('is_verified', false)
            ->update(['is_verified' => true]);
    }

    public function down(): void
    {
        // Not reversible: we can't tell which shops were verified by hand before this ran.
    }
};
