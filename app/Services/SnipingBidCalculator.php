<?php

namespace App\Services;

use App\Models\Card;

class SnipingBidCalculator
{
    public static function calculate(Card $card, EbayService $ebayService, string $gradingType = 'psa'): float
    {
        $fallbackBidAmount = (float) $card->getBidPrice();

        $searchTerms = [$card->search_term];
        $card->load('psaTitles');
        foreach ($card->psaTitles as $psaTitle) {
            $searchTerms[] = $psaTitle->title;
        }
        $searchTerms = array_values(array_unique(array_filter($searchTerms)));

        $byItemId = [];
        foreach ($searchTerms as $searchTerm) {
            $listings = $gradingType === 'cgc'
                ? $ebayService->searchCgc10BuyItNow($searchTerm)
                : $ebayService->searchPsa10BuyItNow($searchTerm);

            foreach ($listings as $row) {
                $itemId = $row['itemId'] ?? '';
                if ($itemId === '') {
                    continue;
                }
                $price = (float) ($row['price'] ?? 0);
                if ($price <= 0) {
                    continue;
                }
                if (!isset($byItemId[$itemId]) || $price < $byItemId[$itemId]) {
                    $byItemId[$itemId] = $price;
                }
            }
        }

        $uniqueListingCount = count($byItemId);
        $lowestPrice = null;
        foreach ($byItemId as $price) {
            if ($lowestPrice === null || $price < $lowestPrice) {
                $lowestPrice = $price;
            }
        }

        if ($uniqueListingCount >= 3 && $lowestPrice !== null && $lowestPrice > 0) {
            $listingBasedBid = self::calculateBidFromTargetProfit($lowestPrice, 0.15);
            if ($listingBasedBid > 0) {
                return $listingBasedBid;
            }
        }

        return $fallbackBidAmount;
    }

    public static function defaultBidInput(?float $suggestedBid, float $currentBid): float
    {
        $value = $suggestedBid ?? round($currentBid + 0.5, 2);

        return $value > 15 ? $value : 15;
    }

    private static function calculateBidFromTargetProfit(float $expectedSalePrice, float $targetProfitMargin): float
    {
        $netAfterFees = self::calculateNetSaleAfterFees($expectedSalePrice);
        if ($netAfterFees <= 0) {
            return 0;
        }

        return (float) floor($netAfterFees / (1 + $targetProfitMargin));
    }

    private static function calculateNetSaleAfterFees(float $salePrice): float
    {
        $net = $salePrice * 0.87;

        if ($salePrice < 100) {
            $net -= 3;
        }

        return $net;
    }
}
