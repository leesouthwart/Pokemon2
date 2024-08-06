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

    public function calcRoi($price)
    {
        if($this->psa_10_price == 0) {
            return 0;
        }

        // Calculate $afterFees
        $afterFees = $this->psa_10_price - (0.155 * $this->psa_10_price); // Subtract 15.5% of $price2

        // Check if $price2 is greater than 30
        if ($this->psa_10_price > 30) {
            $afterFees -= 3; // Subtract 3 if $price2 is greater than 30
        } else {
            $afterFees -= 1.75; // Subtract 2 if $price2 is 30 or less
        }

        // Calculate the adjusted initial price
        $initialPrice = $price + 13;

        // Calculate ROI
        // ROI formula: ((Final Value - Initial Value) / Initial Value) * 100
        $roi = (($afterFees - $initialPrice) / $initialPrice) * 100;

        return number_format($roi, 2);
    }
}
