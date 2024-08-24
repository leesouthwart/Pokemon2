<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
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
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

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
}
