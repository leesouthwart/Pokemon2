<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Define your table name
        $tableName = 'cards';

        // 1. Clean and update existing data
        DB::table($tableName)->whereNotNull('cr_price')->update([
            'cr_price' => DB::raw("REPLACE(cr_price, ',', '')")
        ]);

        // 2. Alter the column type from string to decimal
        Schema::table($tableName, function (Blueprint $table) {
            $table->decimal('cr_price', 15, 2)->change();
            // Adjust precision (15,2) as needed
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Reverse the column type back to string
        Schema::table('your_table_name', function (Blueprint $table) {
            $table->string('cr_price')->change();
        });
    }
};
