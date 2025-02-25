<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\EbayListing;

class BatchListing extends Component
{
    public EbayListing $listing;
    public $title;
    public $quantity;
    public $price;
    public $afterFees;

    protected $rules = [
        'title' => 'required',
        'quantity' => 'required',
        'price' => 'required',
    ];

    public function mount(EbayListing $listing)
    {
        $this->listing = $listing;
        $this->syncWithListing();
    }

    public function render()
    {
        return view('livewire.batch-listing', [
            'currency' => (object)['symbol' => '£'],
        ]);
    }

    private function syncWithListing()
    {
        $this->title = $this->listing->title;
        $this->quantity = $this->listing->quantity;
        $this->price = $this->listing->price;
        $this->calcAfterFees();
    }

    public function updated($field)
    {
        if ($field === 'title') {
            $this->listing->title = $this->title;
            $this->listing->save();
        } elseif ($field === 'quantity') {
            $this->listing->quantity = $this->quantity;
            $this->listing->save();
        } elseif ($field === 'price') {
            $this->listing->price = $this->price;
            $this->listing->save();
            $this->calcAfterFees();
        }
    }

    public function calcAfterFees()
    {
        $price = $this->price - ($this->price * 0.155);

        if($this->price < 30) {
            $this->afterFees = number_format($price - 2.3, 2);
        } else {
            $this->afterFees = number_format($price - 3.3, 2);
        }
    }
}
