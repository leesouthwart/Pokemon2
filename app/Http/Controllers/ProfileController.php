<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\FundIncrease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        // Only allow user with email 'leesouthwart@gmail.com' to update balance
        $validated = $request->validated();
        if (isset($validated['balance']) && $user->email !== 'leesouthwart@gmail.com') {
            unset($validated['balance']); // Remove balance from validated data if user is not authorized
        }
        
        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current-password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function ebaySettingsUpdate(Request $request): RedirectResponse
    {
        $request->validateWithBag('ebaySettingsUpdate', [
            'shipping_cost' => ['required', 'numeric'],
            'ebay_fee' => ['required', 'numeric'],
            'grading_cost' => ['required', 'numeric'],
        ]);

        $user = $request->user();

        $user->shipping_cost = $request->input('shipping_cost');
        $user->ebay_fee = $request->input('ebay_fee');
        $user->grading_cost = $request->input('grading_cost');

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Add balance to user's account
     */
    public function addBalance(Request $request): RedirectResponse
    {
        // Only allow user with email 'leesouthwart@gmail.com' to add balance
        if ($request->user()->email !== 'leesouthwart@gmail.com') {
            abort(403, 'Unauthorized access');
        }

        $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'type' => ['required', 'in:payout,addition'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $amount = (float)$request->input('amount');
        $type = $request->input('type');
        $notes = $request->input('notes');
        
        // Create fund increase record
        FundIncrease::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'type' => $type,
            'notes' => $notes,
        ]);
        
        // Update user balance
        $user->balance += $amount;
        $user->save();

        return Redirect::route('profile.edit')
            ->with('status', 'balance-added')
            ->with('balance_added', $amount)
            ->with('new_balance', $user->balance)
            ->with('fund_type', $type);
    }
}
