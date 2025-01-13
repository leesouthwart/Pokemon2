<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Card;
use App\Models\Buylist;
use App\Events\ProcessingBuyListCard;
use App\Events\CardSuccessfullyAddedToBuylist;
use App\Events\BuylistCardGroupCompleted;

class AddCardToBuylist implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $card;
    public $buylist;
    public $final;
    public $amount;
    public $cardGroupId;

    /**
     * AddCardToBuylist constructor.
     *
     * @param Card $card
     * @param Buylist $buylist
     */
    public function __construct(array $card, Buylist $buylist, $amount, $cardGroupId, bool $final)
    {
        $this->card = $card;
        $this->buylist = $buylist;
        $this->final = $final;
        $this->amount = $amount;
        $this->cardGroupId = $cardGroupId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $successfullyAdded = $this->buylist->cards()->where('card_group_id', $this->cardGroupId)->where('in_stock', true)->count();
        
        if($successfullyAdded == $this->amount) {
            return;
        }

        event(new ProcessingBuyListCard($this->card));

        $card = Card::find($this->card['id']);
        $inStock = $card->CardrushStockCheck();

        $this->buylist->cards()->attach($this->card['id'], ['in_stock' => $inStock, 'card_group_id' => $this->cardGroupId]);

        if($inStock) {
            event(new CardSuccessfullyAddedToBuylist);
        }

        $successfullyAdded = $this->buylist->cards()->where('card_group_id', $this->cardGroupId)->where('in_stock', true)->count();

        if($successfullyAdded == $this->amount) {
            event(new BuylistCardGroupCompleted($this->cardGroupId));
            return;
        }
        
        // If this is the last of the cards we have, check that the cardGroup (ie, AR's, Bangers, etc) is complete for this Buylist.
        if($this->final) {
            $ids = $this->buylist->cards()->where('card_group_id', $this->cardGroupId)->pluck('cards.id')->toArray();

            if($successfullyAdded != $this->amount) {
                $toCheck = $this->amount - $successfullyAdded;

                $cards = Card::whereHas('cardGroups', function ($query) {
                    $query->where('card_cardgroup.card_group_id', $this->cardGroupId);
                })
                ->whereNotIn('cards.id', $ids)
                ->with('regionCards')
                ->get()
                ->sortByDesc('roi')->toArray();
                
                // No more cards in this cardGroup to check. Return, we're done here.
                // We don't fill any unassigned cards to hit the overall total. If only 15 cardGroup cards are available and user requested 20, we add 15.
                // And show that in the UI with a message. The user can then decide what to do.
                if(count($cards) == 0) {
                    event(new BuylistCardGroupCompleted($this->cardGroupId));
                    return;
                }

                if(count($cards) > $toCheck) {
                    $cards = array_slice($cards, 0, ($toCheck * 2));

                    $cardIndex = 0;
                    foreach($cards as $card) {
                        dispatch(new AddCardToBuylist($card, $this->buylist, $toCheck, $this->cardGroupId, $cardIndex == count($cards) - 1));
                        $cardIndex++;
                    }
                }
            }
        }
    }
}
