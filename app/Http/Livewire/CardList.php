<?php

namespace App\Http\Livewire;

use Livewire\Component;

use App\Models\Card;
use Livewire\WithPagination;

class CardList extends Component
{

    use withPagination;

    public $search = '';
    public $sortField = 'id';
    public $sortDirection = 'asc';
    public $selectedCards = [];

    protected $listeners = [
        'selectedCard' => 'handleSelectedCard',
    ];

    public function render()
    {
        $cards = Card::with('regionCards')
            ->where('search_term', 'like', '%' . $this->search . '%')
            ->join('region_cards', 'cards.id', '=', 'region_cards.card_id')
            ->orderBy($this->sortField, $this->sortDirection)
            ->select('cards.*') // Ensure only card fields are selected
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
            Card::find($card)->delete();
        }
    }
}
