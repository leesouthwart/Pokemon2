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
use Illuminate\Support\Facades\Log;

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

        if ($successfullyAdded >= $this->amount) {
            return;
        }

        event(new ProcessingBuyListCard($this->card));

        $card = Card::find($this->card['id']);
        $inStock = $card->CardrushStockCheck();

        $this->buylist->cards()->attach($this->card['id'], ['in_stock' => $inStock, 'card_group_id' => $this->cardGroupId]);

        if ($inStock) {
            event(new CardSuccessfullyAddedToBuylist);
        }

        $successfullyAdded = $this->buylist->cards()->where('card_group_id', $this->cardGroupId)->where('in_stock', true)->count();

        if ($successfullyAdded >= $this->amount) {
            event(new BuylistCardGroupCompleted($this->cardGroupId));
            $this->dispatchFallbackIfNeeded();
            return;
        }

        // If this is the last of the cards we have, check that the cardGroup (ie, AR's, Bangers, etc) is complete for this Buylist.
        if ($this->final) {
            $ids = $this->buylist->cards()->where('card_group_id', $this->cardGroupId)->pluck('cards.id')->toArray();

            if ($successfullyAdded < $this->amount) {
                $toCheck = $this->amount - $successfullyAdded;

                if ($toCheck <= 0) {
                    event(new BuylistCardGroupCompleted($this->cardGroupId));
                    $this->dispatchFallbackIfNeeded();
                    return;
                }

                if ($this->cardGroupId === null) {
                    $cards = Card::whereNotIn('cards.id', $ids)
                        ->with('regionCards')
                        ->get()
                        ->filter(function ($card) {
                            return $card->roi > 40;
                        })
                        ->shuffle()
                        ->values()
                        ->toArray();
                } else {
                    $cards = Card::whereHas('cardGroups', function ($query) {
                        $query->where('card_cardgroup.card_group_id', $this->cardGroupId);
                    })
                    ->whereNotIn('cards.id', $ids)
                    ->with('regionCards')
                    ->get()
                    ->sortByDesc('roi')
                    ->values()
                    ->toArray();
                }

                // No more cards in this cardGroup to check. Return, we're done here.
                if (count($cards) === 0) {
                    $this->logPhaseExhausted($this->cardGroupId, $this->amount, $successfullyAdded, 0);
                    event(new BuylistCardGroupCompleted($this->cardGroupId));
                    $this->dispatchFallbackIfNeeded();
                    return;
                }

                $dispatchCount = min(count($cards), max(1, $toCheck * 2));
                $cards = array_slice($cards, 0, $dispatchCount);

                $cardIndex = 0;
                foreach ($cards as $card) {
                    dispatch(new AddCardToBuylist($card, $this->buylist, $this->amount, $this->cardGroupId, $cardIndex == count($cards) - 1));
                    $cardIndex++;
                }
            }
        }
    }

    private function dispatchFallbackIfNeeded(): void
    {
        // Fallback is only for explicitly selected groups; null group is already fallback flow.
        if ($this->cardGroupId === null) {
            return;
        }

        $groupData = json_decode($this->buylist->card_group_data, true) ?? [];
        if (count($groupData) === 0) {
            return;
        }

        // Ensure all selected groups are either full or exhausted.
        foreach ($groupData as $group) {
            $groupId = $group['id'] ?? null;
            $target = (int) ($group['amount'] ?? 0);

            if (!$groupId || $target <= 0) {
                continue;
            }

            $successful = $this->buylist->cards()
                ->where('card_group_id', $groupId)
                ->where('in_stock', true)
                ->count();

            if ($successful >= $target) {
                continue;
            }

            $triedIds = $this->buylist->cards()
                ->where('card_group_id', $groupId)
                ->pluck('cards.id')
                ->toArray();

            $remainingInGroup = Card::whereHas('cardGroups', function ($query) use ($groupId) {
                $query->where('card_cardgroup.card_group_id', $groupId);
            })->whereNotIn('cards.id', $triedIds)->count();

            if ($remainingInGroup > 0) {
                return;
            }

            $this->logPhaseExhausted($groupId, $target, $successful, $remainingInGroup);
        }

        $totalTarget = (int) $this->buylist->total_cards;
        $totalSuccessful = $this->buylist->cards()->where('in_stock', true)->count();
        $remainingNeeded = $totalTarget - $totalSuccessful;

        if ($remainingNeeded <= 0) {
            return;
        }

        // Avoid duplicate fallback dispatch runs.
        $fallbackAlreadyStarted = $this->buylist->cards()->whereNull('card_group_id')->exists();
        if ($fallbackAlreadyStarted) {
            return;
        }

        $checkedIds = $this->buylist->cards()->pluck('cards.id')->toArray();
        $fallbackCards = Card::whereNotIn('cards.id', $checkedIds)
            ->with('regionCards')
            ->get()
            ->filter(function ($card) {
                return $card->roi > 40;
            })
            ->shuffle()
            ->values()
            ->toArray();

        if (count($fallbackCards) === 0) {
            $this->logPhaseExhausted(null, $totalTarget, $totalSuccessful, 0);
            event(new BuylistCardGroupCompleted(null));
            return;
        }

        $dispatchCount = min(count($fallbackCards), max(1, $remainingNeeded * 2));
        $cardsToDispatch = array_slice($fallbackCards, 0, $dispatchCount);

        $cardIndex = 0;
        foreach ($cardsToDispatch as $card) {
            dispatch(new AddCardToBuylist($card, $this->buylist, $remainingNeeded, null, $cardIndex == count($cardsToDispatch) - 1));
            $cardIndex++;
        }
    }

    private function logPhaseExhausted($cardGroupId, int $target, int $successful, int $remainingCandidates): void
    {
        Log::warning('Buylist phase exhausted before target reached', [
            'buylist_id' => $this->buylist->id,
            'card_group_id' => $cardGroupId,
            'target' => $target,
            'successful' => $successful,
            'remaining_needed' => max(0, $target - $successful),
            'remaining_candidates' => $remainingCandidates,
            'queue_job' => self::class,
        ]);
    }
}
