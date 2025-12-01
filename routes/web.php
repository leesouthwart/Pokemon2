<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Ebay\OAuthController as EbayOAuthController;
use App\Http\Controllers\Ebay\ApiController as EbayApiController;
use App\Http\Controllers\BidController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CardPsaTitleController;
use App\Http\Controllers\CardGroupController;
use App\Http\Controllers\BuylistController;

use App\Models\EbayProfile;
use App\Models\OauthToken;
use App\Models\Region;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Services\EbayUserService;

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
    return view('dashboard');
});

Route::get('/dashboard', function (EbayUserService $ebayUserService) {
    $ebayUsername = null;

    if (Auth::check()) {
        $ebayUsername = $ebayUserService->getUsernameForUser(Auth::user());
    }

    return view('dashboard', [
        'ebayUsername' => $ebayUsername,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/approved', [EbayOAuthController::class, 'create'])->name('oauth_creation');
Route::get('/ebay/login', [EbayOAuthController::class, 'redirectToEbay'])->name('ebay.login');
Route::post('/ebay/create-auction-listing', [EbayApiController::class, 'createAuctionListing'])->middleware('auth')->name('ebay.create-auction-listing');
Route::post('/ebay/verify-listing', [EbayApiController::class, 'verifyListing'])->middleware('auth')->name('ebay.verify-listing');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/add-balance', [ProfileController::class, 'addBalance'])->name('profile.add-balance');
    Route::post('/profile/ebay-settings', [ProfileController::class, 'ebaySettingsUpdate'])->name('profile.ebay-settings.update');
});

Route::middleware(['currency.convert', 'auth'])->group(function () {
    Route::get('cardrush', [CardController::class, 'index'])->name('cardrush');
    Route::post('store_card', [CardController::class, 'store'])->name('card.store');
    Route::get('group', [CardGroupController::class, 'index'])->name('card_group.index');
    Route::get('group/{card_group}', [CardGroupController::class, 'view'])->name('card_group.single');

    Route::get('upload', [BatchController::class, 'create'])->name('batch.create');
    Route::get('batches', [BatchController::class, 'index'])->name('batch.index');
    Route::get('batches/{batch}', [BatchController::class, 'view'])->name('batch.view');

    Route::get('buylist', [BuylistController::class, 'index'])->name('buylist.index');
    Route::get('buylist/{buylist}', [BuylistController::class, 'view'])->name('buylist.view');

    Route::get('psa-japanese-auctions', function () {
        return view('psa-japanese-auctions');
    })->name('psa-japanese-auctions');

    // PSA Title Management Routes
    Route::prefix('cards/psa-title')->name('cards.psa-title.')->group(function () {
        Route::get('/', [CardPsaTitleController::class, 'index'])->name('index');
        Route::get('/search', [CardPsaTitleController::class, 'search'])->name('search');
        Route::get('/{card}/edit', [CardPsaTitleController::class, 'edit'])->name('edit');
        Route::put('/{card}', [CardPsaTitleController::class, 'update'])->name('update');
        Route::post('/{card}/toggle-excluded', [CardPsaTitleController::class, 'toggleExcluded'])->name('toggle-excluded');
    });
});



require __DIR__.'/auth.php';
