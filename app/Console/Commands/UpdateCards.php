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
    protected $signature = 'app:update-cards';

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
        $cards = Card::where('update_hold_until', '<=', now())
            ->inRandomOrder()
            ->take(80)
            ->get();
        
        foreach($cards as $card) {
            dispatch(new UpdateCard($card));
        }

        return true;
    }
}
