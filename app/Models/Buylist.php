<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Buylist extends Model
{
    use HasFactory;

    public $guarded = [];

    public function cards()
    {
        return $this->belongsToMany(Card::class);
    }
}
