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
        Schema::table('distributors', function (Blueprint $table) {
            if (!Schema::hasColumn('distributors', 'slug')) {
                $table->string('slug')->unique()->nullable()->after('company_name');
            }
            if (!Schema::hasColumn('distributors', 'cover_photo_path')) {
                $table->string('cover_photo_path')->nullable()->after('description');
            }
            if (!Schema::hasColumn('distributors', 'phone')) {
                $table->string('phone')->nullable()->after('cover_photo_path');
            }
            if (!Schema::hasColumn('distributors', 'website')) {
                $table->string('website')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('distributors', 'business_hours')) {
                $table->json('business_hours')->nullable()->after('website');
            }
            if (!Schema::hasColumn('distributors', 'social_links')) {
                $table->json('social_links')->nullable()->after('business_hours');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->dropColumn([
                'slug',
                'cover_photo_path',
                'phone',
                'website',
                'business_hours',
                'social_links',
            ]);
        });
    }
};
