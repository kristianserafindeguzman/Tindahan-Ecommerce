<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Carbon\Carbon;

class StoreController extends Controller
{
    /**
     * List approved stores, for consumer browsing.
     *
     * GET /api/stores
     */
    public function index()
    {
        $stores = Store::whereHas('approvalStatus', function ($query) {
            $query->where('status', 'approved');
        })
            ->get()
            ->map(function ($store) {
                return [
                    'id' => $store->store_id,
                    'name' => $store->store_name,
                    'address' => $store->address,
                    'image' => $store->store_picture_url,
                    'isOpen' => $this->isOpenNow($store),
                    'closesAt' => $store->closing_time ? Carbon::parse($store->closing_time)->format('g:i a') : null,
                ];
            });

        return response()->json($stores);
    }

    /**
     * Whether a store is open right now, based on its operating hours/days.
     *
     * Note: seeded stores store `operating_days` as full day names (e.g. "Monday"),
     * while the vendor profile UI saves 3-letter abbreviations (e.g. "Mon") — matching
     * on the first 3 characters (case-insensitively) handles both.
     */
    private function isOpenNow(Store $store): bool
    {
        if (!$store->opening_time || !$store->closing_time) {
            return false;
        }

        $days = $store->operating_days;
        if (!empty($days)) {
            $today = strtolower(substr(now()->format('D'), 0, 3));
            $opensToday = collect($days)->contains(
                fn ($day) => strtolower(substr($day, 0, 3)) === $today
            );

            if (!$opensToday) {
                return false;
            }
        }

        $now = now()->format('H:i:s');
        $opens = Carbon::parse($store->opening_time)->format('H:i:s');
        $closes = Carbon::parse($store->closing_time)->format('H:i:s');

        if ($opens <= $closes) {
            return $now >= $opens && $now <= $closes;
        }

        // Overnight range, e.g. 8:00 pm - 2:00 am.
        return $now >= $opens || $now <= $closes;
    }
}
