<?php

namespace App\Http\Livewire;

use Livewire\Component;

use App\Models\Card;
use Livewire\WithPagination;

class CardList extends Component
{

    use withPagination;


    public function render()
    {
        return view('livewire.card-list',
            [
                'cardList' => Card::paginate(20),
            ]
        );
    }
}
