<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WonBidController extends Controller
{
    /**
     * Display a listing of won bids awaiting confirmation
     */
    public function index()
    {
        // Only allow access to user with email leesouthwart@gmail.com
        if (!Auth::check() || Auth::user()->email !== 'leesouthwart@gmail.com') {
            abort(403, 'Unauthorized access');
        }

        $bids = Bid::where('status', 'won_awaiting_confirmation')
            ->with(['user', 'card'])
            ->orderBy('checked_at', 'desc')
            ->paginate(20);

        return view('bids.won.index', compact('bids'));
    }

    /**
     * Confirm a won bid
     */
    public function confirm($id)
    {
        // Only allow access to user with email leesouthwart@gmail.com
        if (!Auth::check() || Auth::user()->email !== 'leesouthwart@gmail.com') {
            abort(403, 'Unauthorized access');
        }

        $bid = Bid::findOrFail($id);

        if ($bid->status !== 'won_awaiting_confirmation') {
            return redirect()->route('bids.won.index')
                ->with('error', 'Bid is not in a state that can be confirmed.');
        }

        // Update status to confirmed
        $bid->status = 'won_confirmed';
        $bid->save();

        Log::info("Bid {$bid->id} confirmed by user {$bid->user_id}");

        return redirect()->route('bids.won.index')
            ->with('success', 'Bid confirmed successfully.');
    }

    /**
     * Decline a won bid and refund the bid amount
     */
    public function decline($id)
    {
        // Only allow access to user with email leesouthwart@gmail.com
        if (!Auth::check() || Auth::user()->email !== 'leesouthwart@gmail.com') {
            abort(403, 'Unauthorized access');
        }

        $bid = Bid::findOrFail($id);

        if ($bid->status !== 'won_awaiting_confirmation') {
            return redirect()->route('bids.won.index')
                ->with('error', 'Bid is not in a state that can be declined.');
        }

        // Refund the end_price to user balance
        // When the bid won, CheckAuctionResult refunded the bid_amount and deducted the end_price
        // So the net effect was: -end_price from the original balance
        // When declining, we refund the end_price to restore the balance
        $user = $bid->user;
        if ($user) {
            $user->balance += $bid->end_price;
            $user->save();

            Log::info("Bid {$bid->id} declined. Refunded end_price {$bid->end_price} to user {$user->id}");
        }

        // Update status to declined
        $bid->status = 'refunded';
        $bid->save();

        return redirect()->route('bids.won.index')
            ->with('success', 'Bid declined and amount refunded.');
    }
}

