<?php

namespace App\Http\Controllers;

use App\Models\Card;
use Illuminate\Http\Request;

class CardPsaTitleController extends Controller
{
    /**
     * Display a listing of cards with PSA title management
     */
    public function index(Request $request)
    {
        $query = Card::query();
        
        // Apply filters only if not searching
        if (!$request->has('q') || empty($request->get('q'))) {
            // Hide cards with PSA title (enabled by default)
            if ($request->get('hide_with_title', '1') === '1') {
                $query->whereNull('psa_title');
            }
            
            // Hide excluded cards
            if ($request->get('hide_excluded', '1') === '1') {
                $query->where('excluded_from_sniping', false);
            }
        }
        
        $cards = $query->orderBy('id', 'desc')->paginate(50);
        return view('cards.psa-title.index', compact('cards'));
    }

    /**
     * Show the form for editing a card's PSA title
     */
    public function edit(Card $card)
    {
        return view('cards.psa-title.edit', compact('card'));
    }

    /**
     * Update the card's PSA title and excluded status
     */
    public function update(Request $request, Card $card)
    {
        $request->validate([
            'psa_title' => 'nullable|string|max:255',
            'excluded_from_sniping' => 'boolean',
            'additional_bid' => 'nullable|numeric',
        ]);

        $card->update([
            'psa_title' => $request->psa_title,
            'excluded_from_sniping' => $request->has('excluded_from_sniping') ? (bool)$request->excluded_from_sniping : false,
            'additional_bid' => $request->has('additional_bid') && $request->additional_bid !== null ? (float)$request->additional_bid : 1,
        ]);

        // Preserve filter parameters on redirect
        $redirectUrl = route('cards.psa-title.index');
        if ($request->has('hide_with_title')) {
            $redirectUrl .= '?hide_with_title=' . $request->hide_with_title;
        }
        if ($request->has('hide_excluded')) {
            $redirectUrl .= ($request->has('hide_with_title') ? '&' : '?') . 'hide_excluded=' . $request->hide_excluded;
        }

        return redirect($redirectUrl)
            ->with('success', 'Card updated successfully.');
    }

    /**
     * Toggle the excluded status of a card
     */
    public function toggleExcluded(Request $request, Card $card)
    {
        $card->excluded_from_sniping = !$card->excluded_from_sniping;
        $card->save();

        // Preserve filter parameters on redirect
        $redirectUrl = route('cards.psa-title.index');
        $params = [];
        if ($request->has('hide_with_title')) {
            $params['hide_with_title'] = $request->hide_with_title;
        }
        if ($request->has('hide_excluded')) {
            $params['hide_excluded'] = $request->hide_excluded;
        }
        if (!empty($params)) {
            $redirectUrl .= '?' . http_build_query($params);
        }

        return redirect($redirectUrl)
            ->with('success', 'Excluded status toggled successfully.');
    }

    /**
     * Search for cards by PSA title or search term
     * Note: Search bypasses all filters to show everything
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        $cards = Card::where('psa_title', 'like', "%{$query}%")
            ->orWhere('search_term', 'like', "%{$query}%")
            ->orderBy('id', 'desc')
            ->paginate(50);

        return view('cards.psa-title.index', compact('cards', 'query'));
    }
    
    /**
     * Get a single card for modal editing
     */
    public function getCard(Card $card)
    {
        return response()->json([
            'id' => $card->id,
            'search_term' => $card->search_term,
            'psa_title' => $card->psa_title,
            'excluded_from_sniping' => $card->excluded_from_sniping,
            'image_url' => $card->image_url,
        ]);
    }
}

