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
    public $mode = 'graded';

    public $psa10Prices = [];
    public $averagePsa10Prices = [];
    public $rawPrices = [];
    public $averageRawPrices = [];
    public $rois = [];
    public $gradedRoi = 0;

    public $listeners = ['cardUpdated', 'unselect'];
    public $selected = false;

    public $bidPrice;


    public function mount($card, $mode = 'graded')
    {
        $this->card = $card;
        $this->mode = $mode;
        $this->searchTerm = $this->card->search_term;
        $this->setUpPrices();
        $this->calculateColours();
        $this->calculateBidPrice();
    }
    public function render()
    {
        return view('livewire.card');
    }

    public function selectCard()
    {
        $this->dispatch('cardSelected', $this->card->id);
    }

    private function calculateColours()
    {
        $lowest = $this->rois[\App\Models\Region::GB];

        $colours = [
            'light_green' => 'text-green-400',
            'green' => 'text-green-600',
            'orange' => 'text-yellow-500',
            'red' => 'text-red-500',
        ];

        if ($lowest > 75) {
            $this->roiLowestColor =  $colours['light_green'];
        } elseif ($lowest > 50) {
            $this->roiLowestColor =  $colours['green'];
        } elseif ($lowest > 30) {
            $this->roiLowestColor =  $colours['orange'];
        } else {
            $this->roiLowestColor =  $colours['red'];
        }
    }

    public function cardUpdated($card)
    {
        if($this->card->id == $card['id']) {
            $this->mount(CardModel::find($card['id']), $this->mode);
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
