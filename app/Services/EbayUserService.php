<?php

namespace App\Services;

use App\Models\EbayProfile;
use App\Models\OauthToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EbayUserService
{
    /**
     * Fetch the connected eBay username for a user if available.
     */
    public function getUsernameForUser(User $user): ?string
    {
        $profile = EbayProfile::where('user_id', $user->id)->first();
        if (!$profile) {
            return null;
        }

        $token = OauthToken::where('user_id', $user->id)->latest()->first();
        if (!$token || empty($token->token)) {
            return null;
        }

        $environment = app()->environment('local') ? 'local' : 'production';
        $endpoint = config('ebay.endpoints.' . $environment . '.identity');
        if (!$endpoint) {
            return null;
        }

        try {
            $response = Http::withToken($token->token)->get($endpoint);
            //dd($response->json());
            if ($response->successful()) {
                return $response->json('username');
            }

            Log::warning('Unable to fetch eBay identity', [
                'user_id' => $user->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch eBay identity', [
                'user_id' => $user->id,
                'exception' => $e->getMessage(),
            ]);
        }

        return null;
    }
}

