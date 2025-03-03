<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Services\EbayService;

use App\Models\Card;
use App\Models\Region;

class Sidebar extends Component
{

    public array $ebayData = [];
    public $card;
    public bool $loading = false;
    protected EbayService|null $ebayService = null;
    public bool $show = false;

    public $listeners = ['cardSelected'];

    public function __construct()
    {
        $this->ebayService = new EbayService();
    }

    public function render()
    {
        return view('livewire.sidebar');
    }

    public function cardSelected($card)
    {
        $this->loading = true;
        $this->show = true;
        $this->card = Card::find($card);

        if (!isset($this->ebayData[$this->card->id])) {
            $this->ebayData[$this->card->id] = $this->ebayService->getEbayData($this->card->search_term, Region::find(Region::GB));
        }

        $this->loading = false;
        $this->dispatch('cardUpdated', $this->card);
    }
}
