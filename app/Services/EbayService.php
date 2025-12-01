<?php

namespace App\Services;

use App\Services\AccessTokenService;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Card;
use App\Models\Region;
use App\Models\RegionCard;
use Carbon\Carbon;

class EbayService
{
   public $accessToken;


   public function __construct()
   {
       $this->accessToken = (new AccessTokenService)->getAccessToken();
   }

   // Get data from eBay API. Called when Cards are first submitted and when they are focused on. Gets most up to date data and updates the card.
    public function getEbayData($searchTerm, Region $region)
    {
        if(!$region) {
            Log::error('Region not found for ' . $region->id);
            return;
        }

        // @todo figure out a better way to always have PSA 10 in the search term WITHOUT saving it in DB (as this would change front end forms)
        $searchTermEbay = $searchTerm . ' PSA 10';
        $response = Http::withHeaders([
            'X-EBAY-C-MARKETPLACE-ID' => $region->ebay_marketplace_id,
            'X-EBAY-C-ENDUSERCTX' => $region->ebay_end_user_context,
            'Authorization' => 'Bearer ' . $this->accessToken,
        ])->get('https://api.ebay.com/buy/browse/v1/item_summary/search?q=' . $searchTermEbay .'&limit=5&sort=price&filter=itemLocationCountry:' . $region->ebay_country_code);

        $data = $response->json();

        $itemCardPrice = 0;

        if(isset($data['itemSummaries'])) {
            foreach ($data['itemSummaries'] as $item) {
                $items[] = [
                    'title' => $item['title'],
                    'price' => number_format($item['price']['value'] + $item['shippingOptions'][0]['shippingCost']['value'] ?? 0, 2),
                    'image' => $item['image']['imageUrl'],
                    'url' => $item['itemWebUrl'],
                    'seller' => $item['seller'],
                ];

                $itemCardPrice += $item['price']['value'];
                $itemCardPrice += $item['shippingOptions'][0]['shippingCost']['value'] ?? 0;
            }

            $averageItemCardPrice = number_format($itemCardPrice / count($data['itemSummaries']), 2);
            $lowestItemCardPrice = min(array_map(function($item) {
                return floatval(str_replace(',', '', $item['price']));
            }, $items));

        }

        $cardModel = Card::where('search_term', $searchTerm)->first();

        if ($cardModel) {
            RegionCard::updateOrCreate(
                [
                    'card_id' => $cardModel->id,
                    'region_id' => $region->id,
                ],
                [
                    'psa_10_price' => isset($lowestItemCardPrice) ? floatval(str_replace(',', '', $lowestItemCardPrice)) : 0,
                    'average_psa_10_price' => isset($averageItemCardPrice) ? floatval(str_replace(',', '', $averageItemCardPrice)) : 0,
                ]
            );
        } else {
            Log::error('Card not found for ' . $searchTerm);
        }

        return $items ?? [];
    }


    // Functionality for /upload prices
    public function getEbayDataForPsaListing($searchTerm)
    {
        $search = app()->environment('local') ? 'car' : $searchTerm;
        $response = Http::withHeaders([
            'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_GB',
            'X-EBAY-C-ENDUSERCTX' => 'contextualLocation=country%3DUK%2Czip%3DLE77JG',
            'Authorization' => 'Bearer ' . $this->accessToken,
        ])->get('https://api.ebay.com/buy/browse/v1/item_summary/search?q=' . $search .'&limit=3&sort=price&filter=itemLocationCountry:GB');

        $data = $response->json();

        $itemCardPrice = 0;

        if(isset($data['itemSummaries'])) {
            foreach ($data['itemSummaries'] as $item) {
                $items[] = [
                    'price' => $item['price']['value'] + $item['shippingOptions'][0]['shippingCost']['value'] ?? 0,
                ];

                $itemCardPrice += $item['price']['value'];
                $itemCardPrice += $item['shippingOptions'][0]['shippingCost']['value'] ?? 0;
            }

            $averageItemCardPrice = number_format($itemCardPrice / 3, 2);
            $lowestItemCardPrice = min(array_column($items, 'price'));

            return [
                'lowest' => $lowestItemCardPrice,
                'average' => $averageItemCardPrice,
            ];
        }

        return [
            'lowest' => 0.00,
            'average' => 0.00
        ];
    }

    public function calcRoi($price, $price2)
    {
        // Calculate $afterFees
        $afterFees = $price2 - (0.155 * $price2); // Subtract 15.5% of $price2

        // Check if $price2 is greater than 30
        if ($price2 > 30) {
            $afterFees -= 3; // Subtract 3 if $price2 is greater than 30
        } else {
            $afterFees -= 1.75; // Subtract 2 if $price2 is 30 or less
        }

        // Calculate the adjusted initial price
        $initialPrice = $price + 13;

        // Calculate ROI
        // ROI formula: ((Final Value - Initial Value) / Initial Value) * 100
        $roi = (($afterFees - $initialPrice) / $initialPrice) * 100;

        return $roi;
    }

    //@todo - This isnt working because we don't have permission to use the marketplace_insights api
    // https://developer.ebay.com/api-docs/buy/marketplace-insights/overview.html
    public function getSalesData($searchTerm)
    {
        $response = Http::withHeaders([
            'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_GB',
            'X-EBAY-C-ENDUSERCTX' => 'contextualLocation=country%3DUK%2Czip%3DLE77JG',
            'Authorization' => 'Bearer ' . $this->accessToken,
        ])->get('https://api.ebay.com/buy/marketplace_insights/v1_beta/item_sales/search?q=iphone&category_ids=9355&limit=3');

        return $response->json();
    }

    /**
     * Get eBay listings from seller 'psa' with title containing 'japanese psa 10'
     * Only returns auctions ending in the next 24 hours
     */
    public function getPsaJapanesePsa10Auctions()
    {
        // Check if access token is available
        if (!$this->accessToken) {
            Log::error('No access token available for eBay API call');
            return [];
        }

        // Find US region by marketplace ID
        $region = Region::where('ebay_marketplace_id', 'EBAY_US')->first();
        
        if (!$region) {
            Log::error('US region not found in database');
            return [];
        }

        // Build URL with query parameters
        // Note: eBay Browse API filter syntax uses comma-separated values
        $url = 'https://api.ebay.com/buy/browse/v1/item_summary/search';
        
        // Run two separate searches - one for "japanese" and one for "jpn"
        // Then combine the results since eBay doesn't handle OR queries well
        $searchTerms = [
            'japanese "psa 10" "pokemon"',
            'jpn "psa 10" "pokemon"',
        ];
        
        $allItemSummaries = [];
        $seenItemIds = []; // Track item IDs to avoid duplicates
        
        foreach ($searchTerms as $searchTerm) {
            // Build filter - try with seller filter first, but it might not work in all cases
            $filter = 'buyingOptions:{AUCTION}';
            // Note: Seller filter might cause issues if seller doesn't exist or filter syntax is wrong
            // Uncomment if you want to filter by seller: 
            $filter .= ',sellers:{psa}';
            
            $queryParams = http_build_query([
                'q' => $searchTerm,
                'limit' => 200,
                'sort' => 'endingSoonest',
                'filter' => $filter,
            ]);
            
            Log::info('eBay API search request', [
                'search_term' => $searchTerm,
                'filter' => $filter,
                'url' => $url,
            ]);
            
            $response = Http::withHeaders([
                'X-EBAY-C-MARKETPLACE-ID' => $region->ebay_marketplace_id,
                'X-EBAY-C-ENDUSERCTX' => $region->ebay_end_user_context,
                'Authorization' => 'Bearer ' . $this->accessToken,
            ])->get($url . '?' . $queryParams);

            // Check for HTTP errors
            if (!$response->successful()) {
                Log::error('eBay API error in getPsaJapanesePsa10Auctions', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'search_term' => $searchTerm,
                    'url' => $url . '?' . $queryParams,
                ]);
                continue; // Skip this search term and try the next one
            }

            $data = $response->json();
            
            // Check for API-level errors in response
            if (isset($data['errors'])) {
                Log::error('eBay API returned errors', [
                    'errors' => $data['errors'],
                    'search_term' => $searchTerm,
                ]);
                continue; // Skip this search term and try the next one
            }
            
            // Collect item summaries, avoiding duplicates
            if (isset($data['itemSummaries'])) {
                foreach ($data['itemSummaries'] as $item) {
                    $itemId = $item['itemId'] ?? null;
                    if ($itemId && !in_array($itemId, $seenItemIds)) {
                        $allItemSummaries[] = $item;
                        $seenItemIds[] = $itemId;
                    }
                }
            }
        }
        
        // Now process the combined item summaries
        $listings = [];

        if (!empty($allItemSummaries)) {
            $now = now();
            $twentyFourHoursFromNow = $now->copy()->addHours(24);

            foreach ($allItemSummaries as $item) {
                // Check if it's an auction
                if (!isset($item['buyingOptions']) || !in_array('AUCTION', $item['buyingOptions'])) {
                    continue;
                }

                // // Check if title contains either 'japanese' or 'jpn', and also 'psa 10' and 'pokemon' (case insensitive)
                $title = strtolower($item['title'] ?? '');
                $hasJapanese = strpos($title, 'japanese') !== false || strpos($title, 'jpn') !== false;
                if (!$hasJapanese || strpos($title, 'psa 10') === false || strpos($title, 'pokemon') === false) {
                    continue;
                }

                // Check if ending within 24 hours
                if (isset($item['itemEndDate'])) {
                    $endDate = Carbon::parse($item['itemEndDate']);
                    if ($endDate->isAfter($twentyFourHoursFromNow)) {
                        continue; // Skip if ending after 24 hours
                    }
                } else {
                    continue; // Skip if no end date
                }

                // Get current bid price or starting price if no bids yet
                $currentBid = 0;
                $currency = 'USD';
                
                // Check for current bid price first
                if (isset($item['currentBidPrice']['value'])) {
                    $currentBid = $item['currentBidPrice']['value'];
                    $currency = $item['currentBidPrice']['currency'] ?? 'USD';
                } elseif (isset($item['price']['value'])) {
                    // Fall back to price field (which may be starting price if no bids)
                    $currentBid = $item['price']['value'];
                    $currency = $item['price']['currency'] ?? 'USD';
                } elseif (isset($item['startingPrice']['value'])) {
                    // Fall back to starting price
                    $currentBid = $item['startingPrice']['value'];
                    $currency = $item['startingPrice']['currency'] ?? 'USD';
                }

                $listings[] = [
                    'itemId' => $item['itemId'] ?? '',
                    'title' => $item['title'] ?? '',
                    'image' => $item['image']['imageUrl'] ?? '',
                    'currentBid' => $currentBid,
                    'currency' => $currency,
                    'url' => $item['itemWebUrl'] ?? '',
                    'endDate' => $item['itemEndDate'] ?? '',
                ];
            }
        }

        return $listings;
    }

    /**
     * Get policy IDs from eBay Account API, create them if they don't exist
     */
    private function getPolicyIds($userToken)
    {
        $baseUrl = app()->environment('local') 
            ? 'https://api.sandbox.ebay.com' 
            : 'https://api.ebay.com';

        $policies = [
            'fulfillmentPolicyId' => null,
            'paymentPolicyId' => null,
            'returnPolicyId' => null,
        ];

        // Get or create fulfillment policy
        $fulfillmentResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $userToken,
            'Content-Language' => 'en-US',
        ])->get($baseUrl . '/sell/account/v1/fulfillment_policy?marketplace_id=EBAY_US');

        if ($fulfillmentResponse->successful() && isset($fulfillmentResponse->json()['fulfillmentPolicies'])) {
            $fulfillmentPolicies = $fulfillmentResponse->json()['fulfillmentPolicies'];
            if (!empty($fulfillmentPolicies)) {
                $policies['fulfillmentPolicyId'] = $fulfillmentPolicies[0]['fulfillmentPolicyId'];
            }
        }

        // Create fulfillment policy if it doesn't exist
        if (!$policies['fulfillmentPolicyId']) {
            $fulfillmentPolicyName = 'Default Fulfillment Policy';
            $createFulfillmentResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $userToken,
                'Content-Type' => 'application/json',
                'Content-Language' => 'en-US',
            ])->post($baseUrl . '/sell/account/v1/fulfillment_policy', [
                'name' => $fulfillmentPolicyName,
                'marketplaceId' => 'EBAY_US',
                'handlingTime' => [
                    'value' => 1,
                    'unit' => 'DAY'
                ],
                'shippingOptions' => [
                    [
                        'optionType' => 'DOMESTIC',
                        'costType' => 'FLAT_RATE',
                        'shippingServices' => [
                            [
                                'shippingServiceCode' => 'USPSPriority',
                                'shippingCost' => [
                                    'value' => '0.0',
                                    'currency' => 'USD'
                                ],
                                'shippingServiceAdditionalCost' => [
                                    'value' => '0.0',
                                    'currency' => 'USD'
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

            if ($createFulfillmentResponse->successful() && isset($createFulfillmentResponse->json()['fulfillmentPolicyId'])) {
                $policies['fulfillmentPolicyId'] = $createFulfillmentResponse->json()['fulfillmentPolicyId'];
            } else {
                $policies['fulfillmentPolicyError'] = [
                    'status' => $createFulfillmentResponse->status(),
                    'body' => $createFulfillmentResponse->body(),
                    'json' => $createFulfillmentResponse->json(),
                ];
            }
        }

        // Get or create payment policy
        $paymentResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $userToken,
            'Content-Language' => 'en-US',
        ])->get($baseUrl . '/sell/account/v1/payment_policy?marketplace_id=EBAY_US');

        if ($paymentResponse->successful() && isset($paymentResponse->json()['paymentPolicies'])) {
            $paymentPolicies = $paymentResponse->json()['paymentPolicies'];
            if (!empty($paymentPolicies)) {
                $policies['paymentPolicyId'] = $paymentPolicies[0]['paymentPolicyId'];
            }
        }

        // Create payment policy if it doesn't exist
        if (!$policies['paymentPolicyId']) {
            $paymentPolicyName = 'Default Payment Policy';
            $createPaymentResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $userToken,
                'Content-Type' => 'application/json',
                'Content-Language' => 'en-US',
            ])->post($baseUrl . '/sell/account/v1/payment_policy', [
                'name' => $paymentPolicyName,
                'marketplaceId' => 'EBAY_US',
                'immediatePay' => false,
                'paymentMethods' => [
                    [
                        'paymentMethodType' => 'PAYPAL'
                    ]
                ]
            ]);

            if ($createPaymentResponse->successful() && isset($createPaymentResponse->json()['paymentPolicyId'])) {
                $policies['paymentPolicyId'] = $createPaymentResponse->json()['paymentPolicyId'];
            } else {
                $policies['paymentPolicyError'] = [
                    'status' => $createPaymentResponse->status(),
                    'body' => $createPaymentResponse->body(),
                    'json' => $createPaymentResponse->json(),
                ];
            }
        }

        // Get or create return policy
        $returnResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $userToken,
            'Content-Language' => 'en-US',
        ])->get($baseUrl . '/sell/account/v1/return_policy?marketplace_id=EBAY_US');

        if ($returnResponse->successful() && isset($returnResponse->json()['returnPolicies'])) {
            $returnPolicies = $returnResponse->json()['returnPolicies'];
            if (!empty($returnPolicies)) {
                $policies['returnPolicyId'] = $returnPolicies[0]['returnPolicyId'];
            }
        }

        // Create return policy if it doesn't exist
        if (!$policies['returnPolicyId']) {
            $returnPolicyName = 'Default Return Policy';
            $createReturnResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $userToken,
                'Content-Type' => 'application/json',
                'Content-Language' => 'en-US',
            ])->post($baseUrl . '/sell/account/v1/return_policy', [
                'name' => $returnPolicyName,
                'marketplaceId' => 'EBAY_US',
                'returnsAcceptedOption' => 'RETURNS_ACCEPTED',
                'refundMethod' => 'MONEY_BACK',
                'returnPeriod' => [
                    'value' => 30,
                    'unit' => 'DAY'
                ],
                'returnShippingCostPayer' => 'BUYER',
                'restockingFeePercentage' => 'NO_RESTOCKING_FEE',
                'description' => 'Returns accepted within 30 days'
            ]);

            if ($createReturnResponse->successful() && isset($createReturnResponse->json()['returnPolicyId'])) {
                $policies['returnPolicyId'] = $createReturnResponse->json()['returnPolicyId'];
            } else {
                $policies['returnPolicyError'] = [
                    'status' => $createReturnResponse->status(),
                    'body' => $createReturnResponse->body(),
                    'json' => $createReturnResponse->json(),
                ];
            }
        }

        return $policies;
    }

    /**
     * Create an eBay auction listing on sandbox using Inventory API
     * Requires user OAuth token
     */
    public function createAuctionListing($userToken, $title = 'Test Auction Item', $description = 'This is a test auction listing', $startingPrice = 9.99, $duration = 'GTC')
    {
        $baseUrl = app()->environment('local') 
            ? 'https://api.sandbox.ebay.com' 
            : 'https://api.ebay.com';

        // Get or create policy IDs
        $policies = $this->getPolicyIds($userToken);
        
        // For sandbox, we'll proceed with at least fulfillment policy
        // Payment and return policies may have sandbox issues
        if (!$policies['fulfillmentPolicyId']) {
            return [
                'success' => false,
                'error' => 'Failed to retrieve or create fulfillment policy. Please check your eBay account permissions.',
                'policies_found' => $policies
            ];
        }

        // Use default/null for payment and return if they failed (sandbox may have issues)
        // In production, you'd want all three
        $paymentPolicyId = $policies['paymentPolicyId'] ?? null;
        $returnPolicyId = $policies['returnPolicyId'] ?? null;

        // Step 1: Create or replace inventory item
        $sku = 'TEST-' . time(); // Generate unique SKU
        $inventoryItemPayload = [
            'availability' => [
                'shipToLocationAvailability' => [
                    'quantity' => 1
                ]
            ],
            'condition' => 'LIKE_NEW',
            'conditionDescriptors' => [
                [
                    'name' => '27501', // Professional Grader
                    'values' => ['275010'] // PSA - note: values is an array
                ],
                [
                    'name' => '27502', // Grade
                    'values' => ['275020'] // Grade 10 - note: values is an array
                ]
            ],
            'product' => [
                'title' => $title,
                'description' => $description,
                'aspects' => [
                    'Game' => ['Pokémon TCG']
                ],
                'imageUrls' => [
                    'https://i.ebayimg.com/images/g/example.jpg'
                ]
            ],
            'packageWeightAndSize' => [
                'packageType' => 'LETTER',
                'dimensions' => [
                    'length' => 5,
                    'width' => 5,
                    'height' => 1,
                    'unit' => 'INCH'
                ],
                'weight' => [
                    'value' => 1,
                    'unit' => 'POUND'
                ]
            ]
        ];

        $inventoryResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $userToken,
            'Content-Type' => 'application/json',
            'Content-Language' => 'en-US',
            'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_US',
        ])->put(
            $baseUrl . '/sell/inventory/v1/inventory_item/' . $sku,
            $inventoryItemPayload
        );

        if (!$inventoryResponse->successful()) {
            return [
                'success' => false,
                'error' => 'Failed to create inventory item',
                'details' => $inventoryResponse->json()
            ];
        }

        // Step 2: Create offer from inventory item
        // For auctions, we need to use startPrice instead of pricingSummary
        $listingPolicies = [
            'fulfillmentPolicyId' => $policies['fulfillmentPolicyId'],
        ];
        
        if ($paymentPolicyId) {
            $listingPolicies['paymentPolicyId'] = $paymentPolicyId;
        }
        
        if ($returnPolicyId) {
            $listingPolicies['returnPolicyId'] = $returnPolicyId;
        }
        
        // Build offer payload for auction
        // Note: auctionStartPrice must be inside pricingSummary for auctions
        $offerPayload = [
            'sku' => $sku,
            'marketplaceId' => 'EBAY_US',
            'format' => 'AUCTION',
            'listingPolicies' => $listingPolicies,
            'pricingSummary' => [
                'auctionStartPrice' => [
                    'value' => number_format((float)$startingPrice, 2, '.', ''),
                    'currency' => 'USD'
                ]
            ],
            'categoryId' => '183454', // Test category - you may want to make this configurable
            'merchantLocationKey' => 'lee_location',
            'quantity' => 1,
            'listingDuration' => $duration === 'GTC' ? 'DAYS_7' : $duration, // Auctions can't be GTC, use DAYS_7, DAYS_10, etc.
            'tax' => [
                'applyTax' => false,
                'vatPercentage' => 0.0
            ]
        ];

        $offerResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $userToken,
            'Content-Type' => 'application/json',
            'Content-Language' => 'en-US',
            'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_US',
        ])->post(
            $baseUrl . '/sell/inventory/v1/offer',
            $offerPayload
        );

        if (!$offerResponse->successful()) {
            $errorDetails = $offerResponse->json();
            if (!$errorDetails) {
                $errorDetails = [
                    'status' => $offerResponse->status(),
                    'body' => $offerResponse->body(),
                ];
            }
            
            // Add payload for debugging
            $errorDetails['payload_sent'] = $offerPayload;
            
            return [
                'success' => false,
                'error' => 'Failed to create offer',
                'details' => $errorDetails,
                'status_code' => $offerResponse->status(),
                'inventory_item_created' => true
            ];
        }

        $offerId = $offerResponse->json('offerId');

        // Step 3: Publish the offer
        // The publish endpoint just needs the offerId, but we may need to include marketplace context
        $publishPayload = [
            'offerId' => $offerId,
            'listingId' => null // Will be returned after publish
        ];

        $publishResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $userToken,
            'Content-Type' => 'application/json',
            'Content-Language' => 'en-US',
            'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_US',
        ])->post(
            $baseUrl . '/sell/inventory/v1/offer/' . $offerId . '/publish',
            $publishPayload
        );

        if (!$publishResponse->successful()) {
            return [
                'success' => false,
                'error' => 'Failed to publish offer',
                'details' => $publishResponse->json(),
                'offer_id' => $offerId,
                'inventory_item_created' => true
            ];
        }

        $listingId = $publishResponse->json('listingId');
        
        // Construct the eBay listing URL
        $listingUrl = null;
        if ($listingId) {
            if (app()->environment('local')) {
                // Sandbox URL
                $listingUrl = 'https://www.sandbox.ebay.com/itm/' . $listingId;
            } else {
                // Production URL
                $listingUrl = 'https://www.ebay.com/itm/' . $listingId;
            }
        }

        return [
            'success' => true,
            'offer_id' => $offerId,
            'sku' => $sku,
            'listing_id' => $listingId,
            'listing_url' => $listingUrl,
            'details' => $publishResponse->json()
        ];
    }

    /**
     * Get a single item by item ID (useful for sandbox where search indexing may be delayed)
     * This can be used to verify a listing exists even if it doesn't show up in search yet
     */
    public function getItemById($itemId, Region $region = null)
    {
        
        $region = Region::where('ebay_marketplace_id', 'EBAY_US')->first();
        
        
        if (!$region) {
            return null;
        }

        $baseUrl = app()->environment('local') 
            ? 'https://api.sandbox.ebay.com' 
            : 'https://api.ebay.com';

        $response = Http::withHeaders([
            'X-EBAY-C-MARKETPLACE-ID' => $region->ebay_marketplace_id,
            'X-EBAY-C-ENDUSERCTX' => $region->ebay_end_user_context,
            'Authorization' => 'Bearer ' . $this->accessToken,
        ])->get($baseUrl . '/buy/browse/v1/item/' . $itemId);


        //110588571985 - test item id
        
        if ($response->successful()) {
            
            return $response->json();
        }

        return null;
    }

    /**
     * Place a proxy bid on an eBay auction
     * Requires user OAuth token with buy.offer.auction scope
     */
    public function placeProxyBid($userToken, $itemId, $maxAmount, $currency = 'USD')
    {
        $baseUrl = app()->environment('local') 
            ? 'https://api.sandbox.ebay.com' 
            : 'https://api.ebay.com';

        $payload = [
            'maxAmount' => [
                'currency' => $currency,
                'value' => (string)number_format((float)$maxAmount, 2, '.', '')
            ],
            'userConsent' => [
                'adultOnlyItem' => false
            ]
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $userToken,
            'Content-Type' => 'application/json',
            'X-EBAY-C-MARKETPLACE-ID' => 'EBAY_US',
        ])->post(
            $baseUrl . '/buy/offer/v1_beta/bidding/' . $itemId . '/place_proxy_bid',
            $payload
        );

        if (!$response->successful()) {
            $errorDetails = $response->json();
            return [
                'success' => false,
                'error' => 'Failed to place bid',
                'details' => $errorDetails,
                'status_code' => $response->status()
            ];
        }

        return [
            'success' => true,
            'proxy_bid_id' => $response->json('proxyBidId'),
            'details' => $response->json()
        ];
    }
}
