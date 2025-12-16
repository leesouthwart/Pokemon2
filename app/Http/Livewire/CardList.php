<?php

namespace App\Http\Livewire;

use Livewire\Component;

use App\Models\Card;
use App\Models\CardGroup;
use App\Models\Currency;
use Livewire\WithPagination;
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
            ->join('region_cards', 'cards.id', '=', 'region_cards.card_id');
        
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

