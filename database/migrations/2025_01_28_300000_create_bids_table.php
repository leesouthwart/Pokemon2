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
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('pending_bid_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('card_id')->nullable()->constrained()->onDelete('set null');
            $table->string('ebay_item_id');
            $table->string('ebay_title');
            $table->decimal('bid_amount', 10, 2);
            $table->decimal('end_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamp('end_date');
            $table->enum('status', ['pending', 'submitted', 'won_awaiting_confirmation', 'won', 'lost', 'refunded'])->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('ebay_item_id');
            $table->index('status');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};

