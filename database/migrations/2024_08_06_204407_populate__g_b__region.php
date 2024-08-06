<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Region;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $region = Region::where('name', 'GB')->first();

        if(!$region) {
            $region = new Region();
            $region->name = 'GB';
            $region->ebay_marketplace_id = 'EBAY_GB';
            $region->ebay_end_user_context = 'contextualLocation=country%3DUK%2Czip%3DLE77JG';
            $region->ebay_country_code = 'GB';
            $region->currency_id = 1;
            $region->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        $region = Region::where('name', 'GB')->first();

        if($region) {
            $region->delete();
        }
    }
};
