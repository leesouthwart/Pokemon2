<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use GuzzleHttp\Client;
use DOMDocument;
use DOMXPath;

class Card extends Model
{
    use HasFactory, SoftDeletes;

    public $fillable = [
        'search_term',
        'psa_title',
        'excluded_from_sniping',
        'additional_bid',
        'url',
        'price',
        'image_url',
        'update_hold_until',
        'last_checked',
        'old_cr_price'
    ];

    public $appends = ['roi'];

    public function regionCards()
    {
        return $this->hasMany(RegionCard::class);
    }

    public function cardGroups(): BelongsToMany
    {
        return $this->belongsToMany(CardGroup::class, 'card_cardgroup');
    }

    public function getCardDataFromCr($url)
    {
        // FOR LIVE
        $client = new \GuzzleHttp\Client();

        $response = $client->request('GET', config('settings.scrape_url_base') . $url, [
            'headers' => [
                'accept' => 'application/json',
            ],
        ]);


        $json = json_decode($response->getBody()->getContents(), true);
        $jsonData = $json['result']['selectorElements'];
        //
//        dd($jsonData);

//        // FOR TESTING
//        $jsonData = [
//            [
//                "selector" => "#main_img_1",
//                "textNodes" => [""],
//                "htmlElements" => [
//                    '<img src="https://www.cardrush-pokemon.jp/data/cardrushpokemon/_/70726f647563742f43525f533132615f37392e6a7067003430300000660023666666666666.jpg" data-x2="https://www.cardrush-pokemon.jp/data/cardrushpokemon/_/70726f647563742f43525f533132615f37392e6a7067003634350000740023666666666666.jpg" width="400" height="400" id="main_img_1" alt="画像1: ミュウ【AR】{183/172}" data-id="64908">'
//                ]
//            ],
//            [
//                "selector" => "#pricech",
//                "textNodes" => [
//                    "780円"
//                ],
//                "htmlElements" => [
//                    "<span class=\"figure\" id=\"pricech\">680円</span>"
//                ]
//            ]
//        ];

        $dom = new DOMDocument();
        $dom->loadHTML($jsonData[0]['htmlElements'][0]);
        $xpath = new DOMXPath($dom);

        $stock = $jsonData[2];
        $inStock = false;
       
        if (array_key_exists('textNodes', $stock) && is_array($stock['textNodes']) && !empty($stock['textNodes'])) {
            $stock = str_replace('在庫数 ', '', $stock['textNodes'][0]);
            $stock = str_replace('枚', '', $stock);
            $stock = str_replace('×', '', $stock);

            if($stock && $stock > 0) {
                $inStock = true;
            }
        }

        $data = [
            'cr_price' => str_replace('円', '', $jsonData[1]['textNodes'][0]),
            'image_url' => $xpath->evaluate("string(//a/@href)"),
            'stock' => $inStock
        ];

        return $data;
    }

    public function CardrushStockCheck()
    {
        $data = $this->getCardDataFromCr($this->url);
        sleep(5); //self enforced anti-ban limit on scraper.

        return $data['stock'];
    }

    public function getConvertedPriceAttribute($currency = null)
    {
        // Running in command line - need to ensure currency is set
        if(!$currency) {
            $currency = Currency::where('code', session('currency'))->first();
        }

        if(!$currency){
            $currency = Currency::find(Currency::GBP);
        }

        $intVal = intval(str_replace(',', '', $this->cr_price));
        $price = $intVal * $currency->convertFrom->where('id', Currency::JPY)->first()->pivot->conversion_rate;

        return number_format($price, 2, '.', '');
    }

    public function getRoiAttribute()
    {
        $cardRegion = RegionCard::where('card_id', $this->id)->where('region_id', 1)->first();

        return $cardRegion->calcRoi($this->converted_price);
    }

    /**
     * Find a card by matching PSA title with eBay listing title
     * 
     * @param string $ebayTitle The title from an eBay listing
     * @return Card|null
     */
    public static function findByPsaTitle($ebayTitle)
    {
        if (empty($ebayTitle)) {
            return null;
        }

        $ebayTitleLower = strtolower($ebayTitle);

        // Search for cards where the PSA title is contained in the eBay title (case-insensitive)
        return static::whereNotNull('psa_title')
            ->where('excluded_from_sniping', false)
            ->get()
            ->first(function ($card) use ($ebayTitleLower) {
                $psaTitleLower = strtolower($card->psa_title);
                return strpos($ebayTitleLower, $psaTitleLower) !== false;
            });
    }

    /**
     * Calculate the bid price: CR price converted to USD + user grading_cost + card additional_bid
     * 
     * @return float
     */
    public function getBidPrice()
    {
        if (!$this->cr_price) {
            return 0;
        }

        // Get USD currency
        $usdCurrency = Currency::find(Currency::USD);
        if (!$usdCurrency) {
            return 0;
        }

        // Convert CR price (JPY) to USD using the same method as getConvertedPriceAttribute
        $intVal = intval(str_replace(',', '', $this->cr_price));
        
        // Use convertFrom relationship to get conversion rate from JPY to USD
        $conversion = $usdCurrency->convertFrom->where('id', Currency::JPY)->first();
        if (!$conversion || !$conversion->pivot) {
            return 0;
        }

        $usdPrice = $intVal * $conversion->pivot->conversion_rate;

        // Get user grading cost (for user with email leesouthwart@gmail.com)
        // Note: grading_cost is stored in GBP, so we need to convert to USD
        $user = User::where('email', 'leesouthwart@gmail.com')->first();
        $gradingCostGbp = $user && $user->grading_cost ? (float)$user->grading_cost : 0;
        
        // Convert grading cost from GBP to USD
        $gradingCostUsd = 0;
        if ($gradingCostGbp > 0) {
            $gbpCurrency = Currency::find(Currency::GBP);
            if ($gbpCurrency) {
                $gbpToUsdConversion = $gbpCurrency->convertTo()->where('currency_id_2', Currency::USD)->first();
                if ($gbpToUsdConversion && $gbpToUsdConversion->pivot) {
                    $gradingCostUsd = $gradingCostGbp * $gbpToUsdConversion->pivot->conversion_rate;
                }
            }
        }

        // Get card additional bid (default is 1)
        $additionalBid = $this->additional_bid ?? 1;

        // Calculate: cr_price (USD) + user grading_cost (converted to USD) + card additional_bid
        return round($usdPrice + $gradingCostUsd + $additionalBid, 2);
    }
}
