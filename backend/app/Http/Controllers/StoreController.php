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
                    'slug' => $store->slug,
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

    // Use StoreHoursService directly inline
    private function getScheduleInfo(Store $store): array
    {
        return app(\App\Services\StoreHoursService::class)->getScheduleInfo($store);
    }
}
