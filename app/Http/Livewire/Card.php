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

    public $psa10Prices = [];
    public $averagePsa10Prices = [];
    public $rois = [];

    public $listeners = ['cardUpdated', 'unselect'];
    public $selected = false;


    public function mount($card)
    {
        $this->card = $card;
        $this->searchTerm = $this->card->search_term;
        $this->setUpPrices();
        $this->calculateColours();

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
            $this->mount(CardModel::find($card['id']));
        }
    }

    public function setUpPrices()
    {
        $this->psa10Prices[Region::GB] = $this->card->regionCards()->where('region_id', Region::GB)->first()->psa_10_price;
        $this->averagePsa10Prices[Region::GB] = $this->card->regionCards()->where('region_id', Region::GB)->first()->average_psa_10_price;
        $this->rois[Region::GB] = $this->card->regionCards()->where('region_id', Region::GB)->first()->calcRoi($this->card->converted_price);

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
