<?php

namespace App\Http\Livewire;

use Livewire\Component;

use App\Models\CardGroup;

class CardGroupList extends Component
{
    public function render()
    {
        return view('livewire.card-group-list', [
            'card_groups' => CardGroup::all()
        ]);
    }
}
