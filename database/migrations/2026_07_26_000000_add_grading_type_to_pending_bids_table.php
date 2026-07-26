<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_bids', function (Blueprint $table) {
            $table->string('grading_type')->default('psa')->after('card_id');
            $table->index('grading_type');
        });
    }

    public function down(): void
    {
        Schema::table('pending_bids', function (Blueprint $table) {
            $table->dropIndex(['grading_type']);
            $table->dropColumn('grading_type');
        });
    }
};
