<?php

namespace App\Http\Livewire;

use Livewire\Component;

use App\Models\Card;
use App\Models\CardGroup;
use Livewire\WithPagination;
use Flasher\Notyf\Prime\NotyfInterface;

use Illuminate\Support\Facades\DB;


class CardList extends Component
{

    use withPagination;

    public $search = '';
    public $sortField = 'id';
    public $sortDirection = 'asc';
    public $sortByCalcRoi = false;
    public $selectedCards = [];
    public $selectedCardGroupId = 0;
    public $groups;
    public $cardGroup;

    protected $listeners = [
        'selectedCard' => 'handleSelectedCard',
    ];

    public function render()
    {
        $this->groups = CardGroup::all();

        $cardsQuery = $this->cardGroup ? $this->cardGroup->cards() : Card::query();

        $cards = $cardsQuery
            ->with('regionCards')
            ->where('search_term', 'like', '%' . $this->search . '%')
            ->join('region_cards', 'cards.id', '=', 'region_cards.card_id')
            ->orderBy($this->sortField, $this->sortDirection)
            ->select('cards.*')
            ->paginate(20);
              

        return view('livewire.card-list', [
            'cardList' => $cards,
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage(); // Reset to the first page when the search changes
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        }

        $this->sortField = $field;
    }

    public function handleSelectedCard($cardId)
    {
        $this->selectedCards[] = $cardId;
    }

    public function delete()
    {
        foreach($this->selectedCards as $card) {
            $cardModel = Card::find($card);

            if($cardModel) {
                $cardModel->delete();
            }
        }

        $this->dispatch('success', count($this->selectedCards) .' cards successfully deleted');
    }

    public function addToGroup()
    {
        $group = CardGroup::find($this->selectedCardGroupId);

        if($group) {
            foreach($this->selectedCards as $card) {
                $group->cards()->attach($card);
            }
    
            $this->selectedCards = [];
            $this->dispatch('unselect');
            $this->dispatch('success', 'Successfully added to group');
        }
    }

}

