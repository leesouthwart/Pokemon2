<?php

namespace App\Http\Livewire;

use Livewire\Component;

use App\Models\Card;
use Livewire\WithPagination;

class CardList extends Component
{

    use withPagination;

    public $search = '';

    public function render()
    {
        $cards = Card::where('search_term', 'like', '%' . $this->search . '%') // Adjust 'name' to your searchable column
        ->paginate(20);

        return view('livewire.card-list', [
            'cardList' => $cards,
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage(); // Reset to the first page when the search changes
    }
}
