<?php

namespace App\Jobs;

use App\Models\Card;
use App\Models\Region;
use App\Models\RegionCard;
use App\Models\CardGroup;
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
    public $groups;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($searchTerm, $url, $groups)
    {
        $this->searchTerm = $searchTerm;
        $this->url = $url;
        $this->ebayService = new EbayService();
        $this->groups = $groups;
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
        if (!$existingCard) {
            $card = new Card;
            $data = $card->getCardDataFromCr($this->url);
        }

        foreach ($regions as $region) {
            try {
                if (!$existingCard) {
                    
                    $card->search_term = $this->searchTerm;
                    $card->url = $this->url;
                    $card->cr_price = intval(str_replace(',', '', $data['cr_price']));
                    $card->image_url = $data['image_url'];
                    $card->save();

                    // if $this->groups is not empty, turn the comma separated string into an array
                    

                } else {
                    $card = $existingCard;
                }

                $this->ebayService->getEbayData($this->searchTerm, $region);

                $card->roi_average = $card->regionCards()->where('region_id', 1)->first()->calcRoi($card->converted_price);

                $card->save();

                if (!empty($this->groups)) {
                    $this->groups = explode(',', $this->groups);

                    // get groups (by name) and add card to each group
                    foreach ($this->groups as $group) {
                        $cardGroup = CardGroup::where('name', $group)->first();
                        if ($cardGroup) {
                            $cardGroup->cards()->attach($card);
                        }
                    }
                }

            } catch (\Exception $e) {
                Log::error('Error: ' . $e->getMessage());
            }
        }

        sleep(5); // Add a slight delay to prevent rate limiting
    }
}
