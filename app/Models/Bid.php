<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bid extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pending_bid_id',
        'card_id',
        'ebay_item_id',
        'ebay_title',
        'bid_amount',
        'end_price',
        'currency',
        'end_date',
        'status',
        'submitted_at',
        'checked_at',
        'notes',
    ];

    protected $casts = [
        'bid_amount' => 'decimal:2',
        'end_price' => 'decimal:2',
        'end_date' => 'datetime',
        'submitted_at' => 'datetime',
        'checked_at' => 'datetime',
    ];

    /**
     * Get the user that placed this bid
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the pending bid this was created from
     */
    public function pendingBid()
    {
        return $this->belongsTo(PendingBid::class);
    }

    /**
     * Get the card this bid is for
     */
    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}

