<?php

namespace App\Http\Livewire;

use Livewire\Component;

use App\Models\Card;
use App\Models\CardGroup;
use App\Models\Currency;
use App\Models\Region;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Flasher\Notyf\Prime\NotyfInterface;

use Illuminate\Support\Facades\DB;


class CardList extends Component
{

    use withPagination;

    public $search = '';
    public $minPrice = '';
    public $maxPrice = '';
    public $sortField = 'roi_average';
    public $sortDirection = 'desc';
    public $sortByCalcRoi = false;
    public $selectedCards = [];
    public $selectedCardGroupId = 0;
    public $groups;
    public $cardGroup;
    public $mode = 'graded';

    #[Url(as: 'view')]
    public $display = 'grid';

    #[Url(as: 'group')]
    public $groupId = null;

    public function mount($mode = 'graded')
    {
        $this->mode = $mode;

        if ($this->mode === 'raw') {
            $this->sortField = 'raw_roi_average';
        }
    }

    public function sortOptions(): array
    {
        $options = $this->mode === 'raw'
            ? [
                'raw_roi_average' => 'Raw ROI',
                'roi_average' => 'Graded ROI',
                'region_cards.raw_price' => 'Raw eBay price',
            ]
            : [
                'roi_average' => 'ROI',
                'region_cards.psa_10_price' => 'PSA 10 price',
                'region_cards.average_psa_10_price' => 'PSA 10 average',
            ];

        return $options + [
            'cr_price' => 'Buy price',
            'search_term' => 'Name',
            'cards.updated_at' => 'Last updated',
        ];
    }

    public function setDisplay($display)
    {
        $this->display = $display === 'list' ? 'list' : 'grid';
    }

    public function setGroup($groupId = null)
    {
        $this->groupId = $groupId ?: null;
        $this->resetPage();
    }

    public function toggleSortDirection()
    {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    }

    public function clearSelection()
    {
        $this->selectedCards = [];
        $this->dispatch('unselect');
    }

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
            ->join('region_cards', function ($join) {
                $join->on('cards.id', '=', 'region_cards.card_id')
                    ->where('region_cards.region_id', Region::GB);
            });

        if ($this->groupId && !$this->cardGroup) {
            $cards = $cards->whereHas('cardGroups', function ($query) {
                $query->where('card_cardgroup.card_group_id', $this->groupId);
            });
        }
        
        // Get GBP currency and conversion rate from JPY to GBP
        $gbpCurrency = Currency::find(Currency::GBP);
        $conversionRate = 1; // Default fallback
        
        if ($gbpCurrency) {
            $conversion = $gbpCurrency->convertFrom->where('id', Currency::JPY)->first();
            if ($conversion && $conversion->pivot) {
                $conversionRate = $conversion->pivot->conversion_rate;
            }
        }
        
        // Apply min price filter (converting GBP input to JPY for comparison)
        if ($this->minPrice !== '') {
            $minPriceGbp = floatval(str_replace(',', '', $this->minPrice));
            // Convert GBP to JPY: minPriceGbp / conversionRate = minPriceJpy
            $minPriceJpy = $conversionRate > 0 ? $minPriceGbp / $conversionRate : 0;
            $cards = $cards->whereRaw("CAST(REPLACE(cards.cr_price, ',', '') AS DECIMAL(15,2)) >= ?", [$minPriceJpy]);
        }
        
        // Apply max price filter (converting GBP input to JPY for comparison)
        if ($this->maxPrice !== '') {
            $maxPriceGbp = floatval(str_replace(',', '', $this->maxPrice));
            // Convert GBP to JPY: maxPriceGbp / conversionRate = maxPriceJpy
            $maxPriceJpy = $conversionRate > 0 ? $maxPriceGbp / $conversionRate : 0;
            $cards = $cards->whereRaw("CAST(REPLACE(cards.cr_price, ',', '') AS DECIMAL(15,2)) <= ?", [$maxPriceJpy]);
        }
        
        $cards = $cards
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

    public function updatingMinPrice()
    {
        $this->resetPage(); // Reset to the first page when the min price changes
    }

    public function updatingMaxPrice()
    {
        $this->resetPage(); // Reset to the first page when the max price changes
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        }

        $this->sortField = $field;
    }

    public function updatedSortField()
    {
        $this->resetPage();
    }

    public function handleSelectedCard($cardId)
    {
        if (in_array($cardId, $this->selectedCards)) {
            $this->selectedCards = array_values(array_diff($this->selectedCards, [$cardId]));
            return;
        }

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

