<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Card;
use App\Models\Buylist;
use App\Jobs\AddCardToBuylist;

class MakeMeMoney extends Component
{
    public $groups;
    public $selectedGroups = [];
    public $completedGroups = [];

    public $showModal = false;
    public $total = 50;

    
    public $progress = 0;
    public $progressPercentage = 0;
    public $currentText;
    public $generating = false;
    public $completed = false;
    public $buylist;

    protected $listeners = [
        'echo:jobs,ProcessingBuyListCard' => 'processingBuyListCard', 
        'echo:jobs,CardSuccessfullyAddedToBuylist' => 'cardSuccessfullyAdded',
        'echo:jobs,BuylistCardGroupCompleted' => 'cardGroupCompleted'
    ];

    public function render()
    {
        return view('livewire.make-me-money');
    }

    public function addGroup()
    { 
        $this->selectedGroups[] = [
            'id' => $this->groups[count($this->selectedGroups)]->id,
            'name' => $this->groups[count($this->selectedGroups)]->name,
            'amount' => 0
        ];
    }

    public function generate()
    {
        $this->generating = true;

        $stockChecked = [];
        $cardList = [];
        $this->progress = 0;
        $this->buylist = Buylist::create([
            'user_id' => auth()->user()->id,
            'name' => 'Buylist_' . date('Y-m-d'),
            'card_group_data' => json_encode($this->selectedGroups),
            'total_cards' => $this->total
        ]);
        

        foreach($this->selectedGroups as $groupData) {
            $cardList[$groupData['name']] = [];
            $cards = Card::whereHas('cardGroups', function ($query) use ($groupData) {
                $query->where('card_cardgroup.card_group_id', $groupData['id']);
            })
            ->with('regionCards')
            ->get()
            ->sortByDesc('roi')->toArray();

            $randomisationFactor = 2;

            if($groupData['amount'] < 5) {
                $randomisationFactor = 4;
            }
            
            // get the first X elements from the array where X is $groupData['amount'] * Random factor
            $picked = array_slice($cards, 0, $groupData['amount'] * $randomisationFactor);

            // get up to X random elements from $picked where X is $groupData['amount']
            $randomCards = collect($picked)->shuffle()->take($groupData['amount']);

            $cardIndex = 0;
            foreach($randomCards as $card) {
                dispatch(new AddCardToBuylist($card, $this->buylist, $groupData['amount'], $groupData['id'], $cardIndex == count($randomCards) - 1));
                $cardIndex++;
            }
        }

        return $cardList;
    }

    public function changeGroup($key, $event)
    {
        $this->selectedGroups[$key]['id'] = $event;
        $this->selectedGroups[$key]['name'] = $this->groups->where('id', $event)->first()->name;
    }

    public function processingBuyListCard($event)
    {
        $card = $event['card'];
        $this->currentText = 'Stock checking '. $card['search_term'] . '...';
    }

    public function cardSuccessfullyAdded()
    {
        $this->progress += 1;
        $this->progressPercentage = round($this->progress / $this->total * 100);
    }

    public function cardGroupCompleted($event)
    {
        $cardGroupId = $event['cardGroupId'] ?? null;

        // Null group id means fallback/extra-card phase has completed.
        if ($cardGroupId === null) {
            $this->completed = true;
            return;
        }

        // Keep unique completed group IDs only.
        if (!in_array($cardGroupId, $this->completedGroups, true)) {
            $this->completedGroups[] = $cardGroupId;
        }

        // Only mark complete from selected groups when we have actually reached target size.
        // If we are still under target, fallback jobs (if available) should continue.
        if (count($this->completedGroups) >= count($this->selectedGroups) && $this->progress >= $this->total) {
            $this->completed = true;
        }
    }
}
