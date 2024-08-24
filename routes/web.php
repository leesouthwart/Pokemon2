<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Ebay\OAuthController as EbayOAuthController;
use App\Http\Controllers\BidController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CardController;

use App\Models\EbayProfile;
use App\Models\OauthToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

use GuzzleHttp\Client;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/approved', [EbayOAuthController::class, 'create'])->name('oauth_creation');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/ebay-settings', [ProfileController::class, 'ebaySettingsUpdate'])->name('profile.ebay-settings.update');
});

//
Route::get('test', function() {
    $response = Http::withHeaders([
        'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_GB',
        'Authorization' => 'Bearer ' . Cache::get('access_token')
    ])->get('https://api.sandbox.ebay.com/buy/marketplace_insights/v1_beta/item_sales/search?q=iphone&category_ids=9355&limit=3');

    dd($response->json());
    return $response->json();
});

Route::get('test3', function() {
    // FOR LIVE
    $client = new \GuzzleHttp\Client();

    $response = $client->request('GET', config('settings.scrape_ebay_url_base') . 'charizard+psa+10', [
        'headers' => [
            'accept' => 'application/json',
        ],
    ]);

    $json = json_decode($response->getBody()->getContents(), true);
    $jsonData = $json['result']['selectorElements'];

    $dom = new DOMDocument();
    $dom->loadHTML($jsonData[0]['htmlElements'][0]);
    $xpath = new DOMXPath($dom);

    $data = [
        'price' => $xpath->evaluate("string(//span[contains(@class, 's-item__price')])")
    ];

    return $data;
});


Route::get('test2', function() {
    \App\Jobs\CreateCard::dispatch('bulbasaur 337 promo', 'https://www.cardrush-pokemon.jp/product/38082');

    dd('done');
});



Route::middleware(['currency.convert', 'auth'])->group(function () {
    Route::get('cardrush', [CardController::class, 'index'])->name('cardrush');
    Route::post('store_card', [CardController::class, 'store'])->name('card.store');

    Route::get('upload', [BatchController::class, 'create'])->name('batch.create');
});




require __DIR__.'/auth.php';
