<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CardGroup extends Model
{
    use HasFactory;

    public $fillable = ['name', 'use_in_buylist_generation'];

    public function cards(): BelongsToMany
    {
        return $this->belongsToMany(Card::class, 'card_cardgroup');
    }
}
