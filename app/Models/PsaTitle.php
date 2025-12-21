<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PsaTitle extends Model
{
    use HasFactory;

    protected $table = 'card_psa_titles';

    protected $fillable = [
        'card_id',
        'title',
    ];

    /**
     * Get the card that this PSA title belongs to
     */
    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}

