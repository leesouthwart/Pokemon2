<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Currency;
use Illuminate\Http\Request;

class CardPsaTitleController extends Controller
{
    /**
     * Display a listing of cards with PSA title management
     */
    public function index(Request $request)
    {
        $query = Card::query();
        
        // Exclude cards over $200 USD (convert to JPY for comparison)
        $maxUsdPrice = 200;
        $jpyCurrency = Currency::find(Currency::JPY);
        $usdCurrency = Currency::find(Currency::USD);
        
        if ($jpyCurrency && $usdCurrency) {
            // Get conversion rate from JPY to USD (to convert USD to JPY, we use the inverse)
            $jpyToUsdConversion = $usdCurrency->convertFrom->where('id', Currency::JPY)->first();
            if ($jpyToUsdConversion && $jpyToUsdConversion->pivot) {
                // Convert $200 USD to JPY
                // If 1 JPY = X USD, then 200 USD = 200 / X JPY
                $jpyToUsdRate = $jpyToUsdConversion->pivot->conversion_rate;
                $maxJpyPrice = $maxUsdPrice / $jpyToUsdRate;
                
                // Only include cards where cr_price (in JPY) is <= max JPY price
                // Exclude cards with null cr_price since we can't verify they're under $200
                $query->whereNotNull('cr_price')
                      ->whereRaw('CAST(REPLACE(cr_price, ",", "") AS UNSIGNED) <= ?', [$maxJpyPrice]);
            }
        }
        
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
     * Note: Search bypasses hide filters but still applies the $200 price limit
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        $cardQuery = Card::where(function($q) use ($query) {
            $q->where('psa_title', 'like', "%{$query}%")
              ->orWhere('search_term', 'like', "%{$query}%");
        });
        
        // Apply $200 USD price limit (same as index method)
        $maxUsdPrice = 200;
        $jpyCurrency = Currency::find(Currency::JPY);
        $usdCurrency = Currency::find(Currency::USD);
        
        if ($jpyCurrency && $usdCurrency) {
            $jpyToUsdConversion = $usdCurrency->convertFrom->where('id', Currency::JPY)->first();
            if ($jpyToUsdConversion && $jpyToUsdConversion->pivot) {
                $jpyToUsdRate = $jpyToUsdConversion->pivot->conversion_rate;
                $maxJpyPrice = $maxUsdPrice / $jpyToUsdRate;
                
                $cardQuery->whereNotNull('cr_price')
                          ->whereRaw('CAST(REPLACE(cr_price, ",", "") AS UNSIGNED) <= ?', [$maxJpyPrice]);
            }
        }
        
        $cards = $cardQuery->orderBy('id', 'desc')->paginate(50);

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

    /**
     * Bulk exclude selected cards
     */
    public function bulkExclude(Request $request)
    {
        $request->validate([
            'card_ids' => 'required|array',
            'card_ids.*' => 'exists:cards,id',
        ]);

        $cardIds = $request->input('card_ids');
        $count = Card::whereIn('id', $cardIds)->update(['excluded_from_sniping' => true]);

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
            ->with('success', "Successfully excluded {$count} card(s).");
    }
}

