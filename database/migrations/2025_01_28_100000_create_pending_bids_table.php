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
        Schema::create('pending_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained()->onDelete('cascade');
            $table->string('ebay_item_id');
            $table->string('ebay_title');
            $table->string('ebay_image_url')->nullable();
            $table->string('ebay_url');
            $table->decimal('current_bid', 10, 2);
            $table->decimal('bid_amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('end_date')->nullable();
            $table->boolean('bid_submitted')->default(false);
            $table->timestamp('bid_submitted_at')->nullable();
            $table->timestamps();

            $table->index('card_id');
            $table->index('ebay_item_id');
            $table->index('bid_submitted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_bids');
    }
};

