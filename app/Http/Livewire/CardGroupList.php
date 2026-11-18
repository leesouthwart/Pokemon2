<?php

namespace App\Http\Livewire;

use Livewire\Component;

use App\Models\CardGroup;

class CardGroupList extends Component
{
    protected $listeners = ['groupCreated' => '$refresh'];

    public function render()
    {
        $cardGroups = CardGroup::withCount('cards')->orderBy('name')->get();

        $cardGroups->each(function ($group) {
            $group->preview_images = $group->cards()
                ->whereNotNull('image_url')
                ->limit(3)
                ->pluck('image_url');
        });

        return view('livewire.card-group-list', [
            'card_groups' => $cardGroups,
        ]);
    }
}
