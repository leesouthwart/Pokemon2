<?php

namespace App\Jobs;

use App\Models\Card;
use App\Models\Region;
use App\Models\RegionCard;
use App\Services\EbayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateCard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $searchTerm;
    public $url;
    public $ebayService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($searchTerm, $url)
    {
        $this->searchTerm = $searchTerm;
        $this->url = $url;
        $this->ebayService = new EbayService();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $existingCard = Card::where('search_term', $this->searchTerm)->first();

        $regions = Region::all();

        foreach ($regions as $region) {
            try {
                if (!$existingCard) {
                    $card = new Card;

                    $data = $card->getCardDataFromCr($this->url);

                    $card->search_term = $this->searchTerm;
                    $card->url = $this->url;
                    $card->cr_price = intval(str_replace(',', '', $data['cr_price']));
                    $card->image_url = $data['image_url'];
                    $card->save();

                } else {
                    $card = $existingCard;
                }

                $this->ebayService->getEbayData($this->searchTerm, $region);

            } catch (\Exception $e) {
                Log::error('Error: ' . $e->getMessage());
            }
        }

        sleep(10); // Add a slight delay to prevent rate limiting
    }
}
