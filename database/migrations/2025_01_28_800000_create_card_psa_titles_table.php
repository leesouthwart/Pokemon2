<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('card_psa_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->timestamps();

            $table->index('card_id');
            $table->index('title');
            // Prevent duplicate titles for the same card
            $table->unique(['card_id', 'title']);
        });

        // Migrate existing psa_title data from cards table
        // Use DB facade to avoid model relationship issues during migration
        $cards = DB::table('cards')
            ->whereNotNull('psa_title')
            ->select('id', 'psa_title')
            ->get();
        
        foreach ($cards as $card) {
            // Check if title already exists to avoid duplicates
            $exists = DB::table('card_psa_titles')
                ->where('card_id', $card->id)
                ->where('title', $card->psa_title)
                ->exists();
            
            if (!$exists) {
                DB::table('card_psa_titles')->insert([
                    'card_id' => $card->id,
                    'title' => $card->psa_title,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_psa_titles');
    }
};

