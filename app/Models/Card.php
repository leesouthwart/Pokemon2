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
}
