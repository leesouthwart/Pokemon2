<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Buylist;
use App\Models\CardGroup;
use Illuminate\Http\Request;

class BuylistController extends Controller
{
    public function view(Buylist $buylist)
    {
        $cards = $buylist->cards()
            ->wherePivot('in_stock', 1)
            ->with('regionCards')
            ->get();

        $groupMeta = collect(json_decode($buylist->card_group_data, true) ?? []);
        $groupNamesById = $groupMeta
            ->filter(function ($group) {
                return isset($group['id']);
            })
            ->mapWithKeys(function ($group) {
                return [(int) $group['id'] => $group['name'] ?? ('Group ' . $group['id'])];
            });

        // Fill any missing group names from DB.
        $missingGroupIds = $cards
            ->pluck('pivot.card_group_id')
            ->filter(function ($id) {
                return !is_null($id);
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->reject(function ($id) use ($groupNamesById) {
                return $groupNamesById->has($id);
            })
            ->values()
            ->all();

        if (!empty($missingGroupIds)) {
            $dbGroupNames = CardGroup::whereIn('id', $missingGroupIds)
                ->pluck('name', 'id')
                ->mapWithKeys(function ($name, $id) {
                    return [(int) $id => $name];
                });
            $groupNamesById = $groupNamesById->merge($dbGroupNames);
        }

        $cardsByGroupId = $cards->groupBy(function ($card) {
            return is_null($card->pivot->card_group_id) ? 'ungrouped' : (string) (int) $card->pivot->card_group_id;
        });

        $groupedCards = [];

        // Keep selected groups in configured order first.
        foreach ($groupMeta as $group) {
            if (!isset($group['id'])) {
                continue;
            }

            $groupId = (int) $group['id'];
            $groupKey = (string) $groupId;
            if (!$cardsByGroupId->has($groupKey)) {
                continue;
            }

            $groupedCards[] = [
                'id' => $groupId,
                'name' => $groupNamesById->get($groupId, 'Group ' . $groupId),
                'cards' => $cardsByGroupId->get($groupKey),
            ];
        }

        // Add any extra groups not present in saved group metadata.
        foreach ($cardsByGroupId as $groupKey => $groupCards) {
            if ($groupKey === 'ungrouped') {
                continue;
            }

            $groupId = (int) $groupKey;
            $alreadyIncluded = collect($groupedCards)->contains(function ($group) use ($groupId) {
                return $group['id'] === $groupId;
            });

            if ($alreadyIncluded) {
                continue;
            }

            $groupedCards[] = [
                'id' => $groupId,
                'name' => $groupNamesById->get($groupId, 'Group ' . $groupId),
                'cards' => $groupCards,
            ];
        }

        // Ungrouped section always last if present.
        if ($cardsByGroupId->has('ungrouped')) {
            $groupedCards[] = [
                'id' => null,
                'name' => 'Ungrouped',
                'cards' => $cardsByGroupId->get('ungrouped'),
            ];
        }

        return view('buylist.view', [
            'groupedCards' => $groupedCards,
        ]);
    }
}
