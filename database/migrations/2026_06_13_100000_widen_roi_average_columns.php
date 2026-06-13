<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE cards MODIFY roi_average DECIMAL(10, 2) NULL');
        DB::statement('ALTER TABLE cards MODIFY raw_roi_average DECIMAL(10, 2) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE cards MODIFY roi_average DECIMAL(6, 2) NULL');
        DB::statement('ALTER TABLE cards MODIFY raw_roi_average DECIMAL(6, 2) NULL');
    }
};
