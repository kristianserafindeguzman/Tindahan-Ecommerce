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

                $scheduleInfo = $this->getScheduleInfo($store);

                return [
                    'id' => $store->store_id,
                    'name' => $store->store_name,
                    'address' => $store->address,
                    'image' => $store->store_picture_url,
                    'isOpen' => $scheduleInfo['isOpen'],
                    'closesAt' => $scheduleInfo['closesAt'],
                    'scheduleStatusText' => $scheduleInfo['statusText'],
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

    private function getScheduleInfo(Store $store): array
    {
        $days = $store->operating_days;
        $timezone = 'Asia/Manila';
        $now = now()->setTimezone($timezone);
        $todayFull = $now->format('l'); // e.g. "Monday"
        $currentTime = $now->format('H:i');

        $isOpen = false;
        $statusText = 'Closed now';
        $closesAt = null;

        // New per-day schedule format
        if (is_array($days) && count(array_filter(array_keys($days), 'is_string')) > 0) {
            $todaySchedule = $days[$todayFull] ?? null;

            if ($todaySchedule && !empty($todaySchedule['is_open'])) {
                $openTime = $todaySchedule['opening_time'] ?? null;
                $closeTime = $todaySchedule['closing_time'] ?? null;

                if ($openTime && $closeTime) {
                    $opens = Carbon::parse($openTime, $timezone)->format('H:i');
                    $closes = Carbon::parse($closeTime, $timezone)->format('H:i');

                    if ($opens <= $closes) {
                        if ($currentTime >= $opens && $currentTime <= $closes) {
                            $isOpen = true;
                            $closesAt = Carbon::parse($closeTime, $timezone)->format('g:i A');
                            $statusText = 'Open until ' . $closesAt;
                        } elseif ($currentTime < $opens) {
                            $statusText = 'Closed till ' . Carbon::parse($openTime, $timezone)->format('g:i A');
                        }
                    } else {
                        // Overnight logic
                        if ($currentTime >= $opens || $currentTime <= $closes) {
                            $isOpen = true;
                            $closesAt = Carbon::parse($closeTime, $timezone)->format('g:i A');
                            $statusText = 'Open until ' . $closesAt;
                        } elseif ($currentTime > $closes && $currentTime < $opens) {
                            $statusText = 'Closed till ' . Carbon::parse($openTime, $timezone)->format('g:i A');
                        }
                    }
                } else {
                    $isOpen = true;
                    $statusText = 'Open today';
                }
            }

            // Find next opening time if currently closed and not already determined for later today
            if (!$isOpen && $statusText === 'Closed now') {
                for ($i = 1; $i <= 7; $i++) {
                    $nextDate = $now->copy()->addDays($i);
                    $nextDayName = $nextDate->format('l');
                    $nextSchedule = $days[$nextDayName] ?? null;

                    if ($nextSchedule && !empty($nextSchedule['is_open'])) {
                        $nextOpenTime = $nextSchedule['opening_time'] ?? null;
                        if ($nextOpenTime) {
                            $dayStr = ($i === 1) ? '' : $nextDayName . ' ';
                            $statusText = 'Closed till ' . $dayStr . Carbon::parse($nextOpenTime, $timezone)->format('g:i A');
                            break;
                        }
                    }
                }
            }
            
            return [
                'isOpen' => $isOpen,
                'statusText' => $statusText,
                'closesAt' => $closesAt
            ];
        }

        // Legacy flat array format fallback
        if (!$store->opening_time || !$store->closing_time) {
            return ['isOpen' => false, 'statusText' => 'Closed now', 'closesAt' => null];
        }

        $opens = Carbon::parse($store->opening_time, $timezone)->format('H:i');
        $closes = Carbon::parse($store->closing_time, $timezone)->format('H:i');
        $todayShort = strtolower(substr($now->format('D'), 0, 3));
        $opensToday = false;

        if (!empty($days) && is_array($days)) {
            $firstValue = reset($days);
            if (is_string($firstValue)) {
                $opensToday = collect($days)->contains(
                    fn ($day) => strtolower(substr($day, 0, 3)) === $todayShort
                );
            }
        }

        if ($opensToday) {
            if ($opens <= $closes) {
                if ($currentTime >= $opens && $currentTime <= $closes) {
                    $isOpen = true;
                    $closesAt = Carbon::parse($closes, $timezone)->format('g:i A');
                    $statusText = 'Open until ' . $closesAt;
                } elseif ($currentTime < $opens) {
                    $statusText = 'Closed till ' . Carbon::parse($opens, $timezone)->format('g:i A');
                }
            } else {
                if ($currentTime >= $opens || $currentTime <= $closes) {
                    $isOpen = true;
                    $closesAt = Carbon::parse($closes, $timezone)->format('g:i A');
                    $statusText = 'Open until ' . $closesAt;
                } elseif ($currentTime > $closes && $currentTime < $opens) {
                    $statusText = 'Closed till ' . Carbon::parse($opens, $timezone)->format('g:i A');
                }
            }
        }

        if (!$isOpen && $statusText === 'Closed now') {
            if (!empty($days) && is_array($days) && is_string(reset($days))) {
                for ($i = 1; $i <= 7; $i++) {
                    $nextDate = $now->copy()->addDays($i);
                    $nextDayShort = strtolower(substr($nextDate->format('D'), 0, 3));
                    $opensThatDay = collect($days)->contains(
                        fn ($day) => strtolower(substr($day, 0, 3)) === $nextDayShort
                    );

                    if ($opensThatDay) {
                        $dayStr = ($i === 1) ? '' : $nextDate->format('l') . ' ';
                        $statusText = 'Closed till ' . $dayStr . Carbon::parse($store->opening_time, $timezone)->format('g:i A');
                        break;
                    }
                }
            }
        }

        return [
            'isOpen' => $isOpen,
            'statusText' => $statusText,
            'closesAt' => $closesAt
        ];
    }
}
