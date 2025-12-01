<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingBid extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_id',
        'ebay_item_id',
        'ebay_title',
        'ebay_image_url',
        'ebay_url',
        'current_bid',
        'bid_amount',
        'currency',
        'end_date',
        'bid_submitted',
        'bid_submitted_at',
        'status',
    ];

    protected $casts = [
        'current_bid' => 'decimal:2',
        'bid_amount' => 'decimal:2',
        'end_date' => 'datetime',
        'bid_submitted' => 'boolean',
        'bid_submitted_at' => 'datetime',
    ];

    /**
     * Get the card that this pending bid belongs to
     */
    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}

