<?php

namespace App\Http\Controllers;

use App\Models\CardGroup;
use Illuminate\Http\Request;

class CardGroupController extends Controller
{
    public function index()
    {
        return view('card_groups.index', [
            'card_groups' => CardGroup::all()
        ]);
    }

    public function view()
    {
        return 'test for now';
    }
}
