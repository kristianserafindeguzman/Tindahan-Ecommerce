<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Carbon\Carbon;
use App\Services\DistanceService;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    // GET /api/stores — list approved stores for consumer browsing.
    public function index(Request $request, DistanceService $distanceService)
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        $stores = Store::whereHas('approvalStatus', function ($query) {
            $query->where('status', 'approved');
        })
            ->get()
            ->map(function ($store) use ($lat, $lng, $distanceService) {
                $distance = null;
                if ($lat !== null && $lng !== null) {
                    $distance = $distanceService->calculateDistance($lat, $lng, $store->latitude, $store->longitude);
                }

                return [
                    'id' => $store->store_id,
                    'name' => $store->store_name,
                    'address' => $store->address,
                    'image' => $store->store_picture_url,
                    'isOpen' => $this->isOpenNow($store),
                    'closesAt' => $store->closing_time ? Carbon::parse($store->closing_time)->format('g:i a') : null,
                    'distance_meters' => $distance,
                    'latitude' => $store->latitude,
                    'longitude' => $store->longitude,
                ];
            });

        // If consumer coordinates exist, sort by nearest distance first
        if ($lat !== null && $lng !== null) {
            $stores = $stores->sortBy('distance_meters', SORT_REGULAR, false)->values();
        }

        return response()->json($stores);
    }

    // Whether a store is open right now — supports both the per-day schedule format and the legacy flat-array format.
    private function isOpenNow(Store $store): bool
    {
        $days = $store->operating_days;
        $todayFull = now()->format('l'); // e.g. "Monday"

        // New per-day schedule format: { "Monday": { "is_open": true, "opening_time": "07:00", "closing_time": "21:00" }, ... }
        if (is_array($days) && isset($days[$todayFull])) {
            $todaySchedule = $days[$todayFull];
            if (empty($todaySchedule['is_open'])) {
                return false;
            }

            $openTime = $todaySchedule['opening_time'] ?? null;
            $closeTime = $todaySchedule['closing_time'] ?? null;
            if (!$openTime || !$closeTime) {
                return true; // Day is marked open but no specific times
            }

            $now = now()->format('H:i');
            $opens = Carbon::parse($openTime)->format('H:i');
            $closes = Carbon::parse($closeTime)->format('H:i');

            if ($opens <= $closes) {
                return $now >= $opens && $now <= $closes;
            }
            // Overnight range
            return $now >= $opens || $now <= $closes;
        }

        // Legacy flat array format: ["Monday", "Mon", ...]
        if (!$store->opening_time || !$store->closing_time) {
            return false;
        }

        if (!empty($days) && is_array($days)) {
            // Check if array values are strings (legacy format)
            $firstValue = reset($days);
            if (is_string($firstValue)) {
                $today = strtolower(substr(now()->format('D'), 0, 3));
                $opensToday = collect($days)->contains(
                    fn ($day) => strtolower(substr($day, 0, 3)) === $today
                );

                if (!$opensToday) {
                    return false;
                }
            }
        }

        $now = now()->format('H:i:s');
        $opens = Carbon::parse($store->opening_time)->format('H:i:s');
        $closes = Carbon::parse($store->closing_time)->format('H:i:s');

        if ($opens <= $closes) {
            return $now >= $opens && $now <= $closes;
        }

        // Overnight range
        return $now >= $opens || $now <= $closes;
    }
}
