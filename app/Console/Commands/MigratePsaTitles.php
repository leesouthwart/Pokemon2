<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Card;
use App\Models\PsaTitle;
use Illuminate\Support\Facades\DB;

class MigratePsaTitles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psa-titles:migrate {--force : Force migration even if titles already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing psa_title data from cards table to card_psa_titles table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting PSA titles migration...');
        
        // Get all cards with psa_title
        $cards = Card::whereNotNull('psa_title')
            ->where('psa_title', '!=', '')
            ->get();
        
        $this->info("Found {$cards->count()} cards with PSA titles to migrate");
        
        if ($cards->count() === 0) {
            $this->info('No cards with PSA titles found. Migration complete.');
            return Command::SUCCESS;
        }
        
        $migrated = 0;
        $skipped = 0;
        $errors = 0;
        
        $bar = $this->output->createProgressBar($cards->count());
        $bar->start();
        
        foreach ($cards as $card) {
            try {
                // Check if this PSA title already exists for this card
                $exists = $card->psaTitles()
                    ->where('title', $card->psa_title)
                    ->exists();
                
                if ($exists && !$this->option('force')) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }
                
                // If force is enabled and title exists, skip to avoid duplicate constraint error
                if ($exists && $this->option('force')) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }
                
                // Create the PSA title
                $card->psaTitles()->create([
                    'title' => $card->psa_title,
                ]);
                
                $migrated++;
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error migrating card ID {$card->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        // Summary
        $this->info("Migration complete!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Migrated', $migrated],
                ['Skipped (already exists)', $skipped],
                ['Errors', $errors],
                ['Total', $cards->count()],
            ]
        );
        
        // Show cards that have multiple PSA titles
        $cardsWithMultipleTitles = Card::has('psaTitles', '>', 1)
            ->withCount('psaTitles')
            ->get();
        
        if ($cardsWithMultipleTitles->count() > 0) {
            $this->newLine();
            $this->info("Cards with multiple PSA titles: {$cardsWithMultipleTitles->count()}");
            $this->table(
                ['Card ID', 'Search Term', 'PSA Titles Count'],
                $cardsWithMultipleTitles->map(function ($card) {
                    return [
                        $card->id,
                        $card->search_term,
                        $card->psa_titles_count,
                    ];
                })->toArray()
            );
        }
        
        return Command::SUCCESS;
    }
}

