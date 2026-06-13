<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('region_cards', function (Blueprint $table) {
            $table->decimal('raw_price', 8, 2)->default(0)->after('average_psa_10_price');
            $table->decimal('average_raw_price', 8, 2)->default(0)->after('raw_price');
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->decimal('raw_roi_average', 6, 2)->nullable()->after('roi_average');
        });

        Schema::table('buylists', function (Blueprint $table) {
            $table->string('pricing_mode')->default('graded')->after('total_cards');
        });
    }

    public function down(): void
    {
        Schema::table('region_cards', function (Blueprint $table) {
            $table->dropColumn(['raw_price', 'average_raw_price']);
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn('raw_roi_average');
        });

        Schema::table('buylists', function (Blueprint $table) {
            $table->dropColumn('pricing_mode');
        });
    }
};
