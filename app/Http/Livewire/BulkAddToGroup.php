<?php

namespace App\Http\Livewire;

use App\Models\Card;
use App\Models\CardGroup;
use Livewire\Component;
use Livewire\WithPagination;

class BulkAddToGroup extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $selectedGroupId = null;
    public string $newGroupName = '';
    public bool $useInBuylistGeneration = true;
    public bool $hideAlreadyInGroup = true;

    public array $selectedCards = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedGroupId' => ['except' => null],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedGroupId()
    {
        $this->resetPage();
    }

    public function updatedHideAlreadyInGroup()
    {
        $this->resetPage();
    }

    public function createGroup()
    {
        $this->validate([
            'newGroupName' => 'required|string|min:1|max:255',
        ]);

        $group = CardGroup::create([
            'name' => $this->newGroupName,
            'use_in_buylist_generation' => $this->useInBuylistGeneration ? 1 : 0,
        ]);

        $this->newGroupName = '';
        $this->selectedGroupId = $group->id;

        $this->dispatch('success', "Created group \"{$group->name}\"");
    }

    public function selectAllOnPage(array $ids)
    {
        $existing = array_map('strval', $this->selectedCards);
        foreach ($ids as $id) {
            if (!in_array((string) $id, $existing, true)) {
                $this->selectedCards[] = (string) $id;
                $existing[] = (string) $id;
            }
        }
    }

    public function clearSelection()
    {
        $this->selectedCards = [];
    }

    public function addSelectedToGroup()
    {
        if (!$this->selectedGroupId) {
            $this->dispatch('error', 'Please select a group first');
            return;
        }

        if (empty($this->selectedCards)) {
            $this->dispatch('error', 'No cards selected');
            return;
        }

        $group = CardGroup::find($this->selectedGroupId);
        if (!$group) {
            $this->dispatch('error', 'Selected group not found');
            return;
        }

        // syncWithoutDetaching avoids duplicate pivot rows for cards already in the group.
        $group->cards()->syncWithoutDetaching($this->selectedCards);

        $count = count($this->selectedCards);
        $this->selectedCards = [];

        $this->dispatch('success', "Added {$count} " . \Illuminate\Support\Str::plural('card', $count) . " to \"{$group->name}\"");
    }

    public function render()
    {
        $query = Card::query()
            ->with('cardGroups')
            ->when($this->search !== '', function ($q) {
                $q->where('search_term', 'like', '%' . $this->search . '%');
            });

        if ($this->hideAlreadyInGroup && $this->selectedGroupId) {
            $groupId = $this->selectedGroupId;
            $query->whereDoesntHave('cardGroups', function ($q) use ($groupId) {
                $q->where('card_groups.id', $groupId);
            });
        }

        $cards = $query->orderBy('search_term')->paginate(50);

        return view('livewire.bulk-add-to-group', [
            'cards' => $cards,
            'groups' => CardGroup::orderBy('name')->get(),
            'pageCardIds' => $cards->pluck('id')->all(),
            'selectedGroup' => $this->selectedGroupId ? CardGroup::find($this->selectedGroupId) : null,
        ]);
    }
}
