<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\CardGroup;

class CardGroupForm extends Component
{
    public String $name = '';

    public function render()
    {
        return view('livewire.card-group-form');
    }

    public function submit()
    {
        CardGroup::create([
            'name' => $this->name,
            'use_in_buylist_generation' => 1
        ]);

        $this->resetVars();
    }

    public function resetVars()
    {
        $this->name = '';
    }
}
