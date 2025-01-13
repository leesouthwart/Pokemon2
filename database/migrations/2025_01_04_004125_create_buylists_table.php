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
        Schema::create('buylists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->json('card_group_data');
            $table->unsignedBigInteger('total_cards');
            $table->timestamps();
        });

        // create buylist_cards table
        Schema::create('buylist_card', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('buylist_id');
            $table->unsignedBigInteger('card_id');
            $table->unsignedBigInteger('card_group_id')->nullable();
            $table->boolean('in_stock');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buylists');
        Schema::dropIfExists('buylist_card');
    }
};
