<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Card;
use App\Models\Region;
use App\Models\RegionCard;
use App\Services\EbayService;

class UpdateCard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $card; 
    public $ebayService;

    public function __construct($card)
    {
        $this->card = $card;
        $this->ebayService = new EbayService();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $card = $this->card;

        try {
            $data = $card->getCardDataFromCr($card->url);
            $card->old_cr_price = $card->cr_price;
            $card->cr_price = intval(str_replace(',', '', $data['cr_price']));
    
            $regions = Region::all();
    
            foreach ($regions as $region) {
                $this->ebayService->getEbayData($card->search_term, $region);
            }

            // freeze updating on cheap cards, low roi cards, very expensive cards, or cards with no ebay data
            if (
                $card->cr_price < 700
                || $card->regionCards()->where('region_id', 1)->first()->calcRoi($card->converted_price) < 0.3
                || $card->cr_price > 27500
            ) {
                $card->last_checked = now();
                $card->update_hold_until = now()->addDays(90);
            } else {
                $card->last_checked = now();
                $card->update_hold_until = now()->addDays(14);
            }

            $card->roi_average = $card->regionCards()->where('region_id', 1)->first()->calcRoi($card->converted_price);

            $card->save();

        } catch(\Exception $e) {
            \Log::error('Error updating card: ' . $card->search_term);
            \Log::error($e);
        }
    }
}
