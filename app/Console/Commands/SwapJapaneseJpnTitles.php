<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Card;

class SwapJapaneseJpnTitles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psa-titles:swap-japanese-jpn
        {--dry-run : Preview changes without writing to the database}
        {--card= : Only process the card with this ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'For each card with a psa_title or card_psa_title, create a new card_psa_title swapping "japanese" <-> "jpn"';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $cardIdFilter = $this->option('card');

        $query = Card::query()
            ->where(function ($q) {
                $q->whereNotNull('psa_title')
                    ->where('psa_title', '!=', '')
                    ->orWhereHas('psaTitles');
            })
            ->with('psaTitles');

        if ($cardIdFilter !== null) {
            $query->where('id', $cardIdFilter);
        }

        $cards = $query->get();

        $this->info("Found {$cards->count()} cards to scan" . ($dryRun ? ' (dry run)' : ''));

        if ($cards->isEmpty()) {
            return Command::SUCCESS;
        }

        $created = 0;
        $skippedDuplicate = 0;
        $skippedNoMatch = 0;

        $bar = $this->output->createProgressBar($cards->count());
        $bar->start();

        foreach ($cards as $card) {
            $sourceTitles = [];

            if (!empty($card->psa_title)) {
                $sourceTitles[] = $card->psa_title;
            }

            foreach ($card->psaTitles as $psaTitle) {
                $sourceTitles[] = $psaTitle->title;
            }

            $sourceTitles = array_values(array_unique(array_filter(array_map('trim', $sourceTitles))));

            $existingTitlesLower = [];
            foreach ($sourceTitles as $t) {
                $existingTitlesLower[strtolower($t)] = true;
            }

            $newTitlesForCard = [];

            foreach ($sourceTitles as $title) {
                $swapped = $this->swapJapaneseJpn($title);

                if ($swapped === null || $swapped === $title) {
                    $skippedNoMatch++;
                    continue;
                }

                $swappedLower = strtolower($swapped);

                if (isset($existingTitlesLower[$swappedLower]) || isset($newTitlesForCard[$swappedLower])) {
                    $skippedDuplicate++;
                    continue;
                }

                $newTitlesForCard[$swappedLower] = $swapped;

                if (!$dryRun) {
                    $card->psaTitles()->create([
                        'title' => $swapped,
                    ]);
                } else {
                    $this->newLine();
                    $this->line("Card {$card->id}: \"{$title}\" -> \"{$swapped}\"");
                }

                $created++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Status', 'Count'],
            [
                [$dryRun ? 'Would create' : 'Created', $created],
                ['Skipped (duplicate)', $skippedDuplicate],
                ['Skipped (no japanese/jpn match)', $skippedNoMatch],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Swap whole-word occurrences of "japanese" <-> "jpn" while preserving the
     * original casing pattern (all upper, all lower, title case, or mixed).
     *
     * Returns the swapped string, or null if neither token is present.
     */
    private function swapJapaneseJpn(string $title): ?string
    {
        if (!preg_match('/\b(japanese|jpn)\b/i', $title)) {
            return null;
        }

        return preg_replace_callback('/\b(japanese|jpn)\b/i', function ($matches) {
            $word = $matches[1];
            $lower = strtolower($word);
            $replacement = $lower === 'japanese' ? 'jpn' : 'japanese';

            if ($word === strtoupper($word)) {
                return strtoupper($replacement);
            }

            if ($word === strtolower($word)) {
                return strtolower($replacement);
            }

            if ($word === ucfirst(strtolower($word))) {
                return ucfirst(strtolower($replacement));
            }

            return $replacement;
        }, $title);
    }
}
