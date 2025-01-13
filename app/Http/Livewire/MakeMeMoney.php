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
        $totalOfSelectedGroups = 0;
        $totalOfSelectedGroups = array_sum(array_column($this->selectedGroups, 'amount'));

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

            // get X random elements from $picked where X is $groupData['amount']
            $randomCards = collect($picked)->random($groupData['amount']);

            $cardIndex = 0;
            foreach($randomCards as $card) {
                dispatch(new AddCardToBuylist($card, $this->buylist, $groupData['amount'], $groupData['id'], $cardIndex == count($randomCards) - 1));
                $cardIndex++;
            }
        }

        if($totalOfSelectedGroups < $this->total) {
            $remaining = $this->total - $totalOfSelectedGroups;
            $selectedGroupIds = array_column($this->selectedGroups, 'id');

            $remainingCards = Card::where(function ($query) use ($selectedGroupIds) {
                $query->whereHas('cardGroups', function ($query) use ($selectedGroupIds) {
                    $query->whereNotIn('card_cardgroup.card_group_id', $selectedGroupIds);
                })->orWhereDoesntHave('cardGroups');
            })->with('regionCards')
            ->get()
            ->sortByDesc('roi')->toArray();

            $picked = array_slice($remainingCards, 0, $remaining * 10);

            $randomCards = collect($picked)->random($remaining * 4);

            $cardIndex = 0;
            foreach($randomCards as $card) {
                dispatch(new AddCardToBuylist($card, $this->buylist, $remaining, null, $cardIndex == count($randomCards) - 1));
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
        // get the $this->selectedGroups element where id = $event['id']
        // selectedGroups is an array of arrays
        $this->completedGroups[] = $event['cardGroupId'];

        if(count($this->completedGroups) == count($this->selectedGroups)) {
            $this->completed = true;
        }

        if($event == null) { // if event is null then the emitted group is the 'uncategorised' group aka extra cards
            $this->completed = true;
        }
    }
}
