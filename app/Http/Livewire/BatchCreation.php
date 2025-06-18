<?php

namespace App\Http\Livewire;

use Livewire\Component;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Exports\EbayListingExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Attributes\Computed;

use Illuminate\Support\Str;

class BatchCreation extends Component
{

    public string $start = '';
    public string $end = '';
    public $batch;
    public $listings;
    public bool $loading = false;
    public bool $useList = false;
    public string $list = '';
    public bool $psa_api_expired = false;
    public float $calculatedTotal = 0;
    protected $listeners = ['echo:jobs,JobCompleted' => 'handleListingDone'];

    public function mount($batch = null)
    {
        $this->batch = $batch;
        $this->listings = $this->batch->ebayListings ?? null;
    }

    public function render()
    {
        return view('livewire.batch-creation');
    }

    public function submit()
    {
        $this->batch = \App\Models\Batch::create([
            'name' => Carbon::now() . '_' . uniqid(),
            'user_id' => auth()->id()
        ]);

        $this->loading = true;
        if(!$this->useList) {
            for ($i = $this->start; $i <= $this->end; $i++) {
                \App\Jobs\CreateEbayListing::dispatch($i, $this->batch);
            }
        } else {
            $list = explode(',', $this->list);

            foreach($list as $i) {
                \App\Jobs\CreateEbayListing::dispatch($i, $this->batch);
            }
        }
        
    }

    public function handleListingDone()
    {
        // Refresh the listings
        $this->listings = $this->batch?->ebayListings;

        if(Queue::size() == 0) {
            $this->loading = false;
        }

        if (\Cache::get('psa_api_expired')) {
            $this->loading = false;
            $this->psa_api_expired = true;
        }
    }

    public function export()
    {
        $this->calculatedTotal = $this->batch->ebayListings->sum('price');
        return Excel::download(new EbayListingExport($this->batch), 'listings.xlsx');
    }


    #[Computed] 
    public function parentKey()
    {
        return Str::random(6);
    }
}
