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
    public $psaTitle;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($searchTerm, $url, $groups, $psaTitle = null)
    {
        $this->searchTerm = $searchTerm;
        $this->url = $url;
        $this->ebayService = new EbayService();
        $this->groups = $groups;
        $this->psaTitle = $psaTitle;
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
                    if ($this->psaTitle) {
                        $card->psa_title = $this->psaTitle;
                    }
                    $card->save();

                    if (
                        $card->cr_price < 700
                        || $card->cr_price > 27500
                    ) {
                        $card->last_checked = now();
                        $card->update_hold_until = now()->addDays(90);
                    } else {
                        $card->last_checked = now();
                        $card->update_hold_until = now()->addDays(31);
                    }
                } else {
                    $card = $existingCard;
                    // Update psa_title if provided and card doesn't have one
                    if ($this->psaTitle && !$card->psa_title) {
                        $card->psa_title = $this->psaTitle;
                        $card->save();
                    }
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
