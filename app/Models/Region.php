<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    public $fillable = [
        'name',
        'ebay_marketplace_id',
        'ebay_end_user_context',
        'ebay_country_code',
        'currency_id',
    ];

    const GB = 1;

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function regionCards()
    {
        return $this->hasMany(RegionCard::class);
    }
}
