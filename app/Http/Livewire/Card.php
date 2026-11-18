<?php

namespace App\Http\Livewire;

use Livewire\Component;

use App\Models\Card as CardModel;
use Livewire\WithPagination;

use App\Models\Region;

class Card extends Component
{
    public CardModel $card;
    public $searchTerm;
    public $roiLowestColor;
    public $roiBadge;
    public $mode = 'graded';
    public $display = 'list';

    public $psa10Prices = [];
    public $averagePsa10Prices = [];
    public $rawPrices = [];
    public $averageRawPrices = [];
    public $rois = [];
    public $gradedRoi = 0;

    public $listeners = ['cardUpdated', 'unselect'];
    public $selected = false;

    public $bidPrice;


    public function mount($card, $mode = 'graded', $display = null)
    {
        $this->card = $card;
        $this->mode = $mode;
        $this->display = $display ?? $this->display;
        $this->searchTerm = $this->card->search_term;
        $this->setUpPrices();
        $this->calculateColours();
        $this->calculateBidPrice();
    }
    public function render()
    {
        return view($this->display === 'grid' ? 'livewire.card-tile' : 'livewire.card');
    }

    public function selectCard()
    {
        $this->dispatch('cardSelected', $this->card->id);
    }

    private function calculateColours()
    {
        $lowest = $this->rois[\App\Models\Region::GB] ?? 0;

        if ($lowest > 75) {
            $this->roiLowestColor = 'text-emerald-300';
            $this->roiBadge = 'bg-emerald-400 text-emerald-950';
        } elseif ($lowest > 50) {
            $this->roiLowestColor = 'text-lime-300';
            $this->roiBadge = 'bg-lime-400 text-lime-950';
        } elseif ($lowest > 30) {
            $this->roiLowestColor = 'text-yellow-300';
            $this->roiBadge = 'bg-yellow-400 text-yellow-950';
        } else {
            $this->roiLowestColor = 'text-rose-300';
            $this->roiBadge = 'bg-rose-500 text-white';
        }
    }

    public function cardUpdated($card)
    {
        if($this->card->id == $card['id']) {
            $this->mount(CardModel::find($card['id']), $this->mode, $this->display);
        }
    }

    public function setUpPrices()
    {
        $regionCard = $this->card->regionCards()->where('region_id', Region::GB)->first();

        if (!$regionCard) {
            return;
        }

        $this->psa10Prices[Region::GB] = $regionCard->psa_10_price;
        $this->averagePsa10Prices[Region::GB] = $regionCard->average_psa_10_price;
        $this->rawPrices[Region::GB] = $regionCard->raw_price;
        $this->averageRawPrices[Region::GB] = $regionCard->average_raw_price;

        if ($this->mode === 'raw') {
            $this->rois[Region::GB] = $regionCard->calcRawRoi($this->card->converted_price);
            $this->gradedRoi = $regionCard->calcRoi($this->card->converted_price);
        } else {
            $this->rois[Region::GB] = $regionCard->calcRoi($this->card->converted_price);
        }
    }

    public function calculateBidPrice()
    {
        // Get the raw price from the card
        $rawPrice = $this->card->converted_price ?? null;
        
        // If no raw price, set bid price to null
        if ($rawPrice === null) {
            $this->bidPrice = null;
            return;
        }
        
        // Add the user's grading cost
        $user = auth()->user();
        $gradingCost = $user ? $user->grading_cost : 12.5; // Default to 12.5 if no user
        $totalPrice = $rawPrice + $gradingCost;
        
        // Convert to USD using currency conversion
        $gbpCurrency = \App\Models\Currency::find(\App\Models\Currency::GBP);
        $usdCurrency = \App\Models\Currency::find(\App\Models\Currency::USD);
        
        if ($gbpCurrency && $usdCurrency) {
            $conversion = $gbpCurrency->convertTo()->where('currency_id_2', \App\Models\Currency::USD)->first();
            if ($conversion) {
                $this->bidPrice = $totalPrice * $conversion->pivot->conversion_rate;
                
            } else {
                $this->bidPrice = null; // Fallback to null if conversion not found
            }
        } else {
            $this->bidPrice = null; // Fallback to null if currencies not found
        }
    }

   
    public function emitSelectedCard($cardId)
    {
        $this->dispatch('selectedCard', $cardId);
    }

    public function unselect()
    {
        $this->selected = false;
    }

    
}
