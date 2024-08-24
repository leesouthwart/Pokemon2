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

    public function render()
    {
        $cards = Card::where('search_term', 'like', '%' . $this->search . '%') // Adjust 'name' to your searchable column
            ->orderBy($this->sortField, $this->sortDirection)
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
}
