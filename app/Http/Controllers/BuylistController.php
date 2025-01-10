<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Buylist;
use Illuminate\Http\Request;

class BuylistController extends Controller
{
    public function view(Buylist $buylist)
    {
       
        $cards = $buylist->cards()->where('in_stock', 1)->with('regionCards')->get();

        return view('buylist.view', [
            'cards' => $cards
        ]);
    }
}
