<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegionCard extends Model
{
    use HasFactory;

    public $fillable = [
        'region_id',
        'card_id',
        'psa_10_price',
        'average_psa_10_price',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function card()
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * Calculates the ROI of this region card for the given price.
     *
     * The ROI is calculated as ((Final Value - Initial Value) / Initial Value) * 100
     * where Final Value is the PSA 10 price minus the users shipping cost and ebay fee,
     * and Initial Value is the price plus the users grading cost.
     *
     * @param float $price The Currency Converted price. Price must be in the same currency as the RegionCard PSA 10 price
     * @return string
     */
    public function calcRoi($price)
    {
        if($this->psa_10_price == 0) {
            return 0;
        }

        $price = floatval($price);

        // Calculate $afterFees
        $afterFees = $this->psa_10_price - ((auth()->user()->ebay_fee ?? 0.155) * $this->psa_10_price); // Subtract users ebay fee


        $afterFees -= auth()->user() ? auth()->user()->shipping_cost : 3;

        // Calculate the adjusted initial price
        $initialPrice = $price + (auth()->user() ? auth()->user()->grading_cost : 13);

        // Calculate ROI
        // ROI formula: ((Final Value - Initial Value) / Initial Value) * 100
        $roi = (($afterFees - $initialPrice) / $initialPrice) * 100;

        return number_format($roi, 2);
    }
}
