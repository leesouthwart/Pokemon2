<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\UpdateCard;
use App\Models\Card;

class UpdateCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-cards {--force : Ignore hold dates and queue up to 80 random cards}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'handles dispatching the job which updates the CR and Ebay prices of cards.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = $this->option('force')
            ? Card::query()
            : Card::dueForUpdate();

        $eligible = (clone $query)->count();
        $cards = $query->inRandomOrder()->take(80)->get();

        $this->info("Eligible cards: {$eligible}. Dispatching {$cards->count()} update jobs.");

        foreach ($cards as $card) {
            dispatch(new UpdateCard($card));
        }

        return true;
    }
}
