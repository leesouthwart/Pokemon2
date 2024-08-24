<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->float('shipping_cost')->default(3.0);
            $table->float('ebay_fee', 4, 3)->default(0.155);
            $table->float('grading_cost', 4, 2)->default(12.5);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('shipping_cost');
            $table->dropColumn('ebay_fee');
            $table->dropColumn('grading_cost');
        });
    }
};
