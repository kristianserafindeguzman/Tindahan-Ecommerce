<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inventory;
use Barryvdh\DomPDF\Facade\Pdf;

class VendorController extends Controller
{
    /**
     * Get vendor dashboard statistics.
     *
     * GET /api/vendor/stats
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        if (!$user->store) {
            return response()->json([
                'placed_orders' => 0,
                'preparing_orders' => 0,
                'picked_up_orders' => 0,
                'cancelled_orders' => 0,
                'recent_orders' => []
            ]);
        }
        
        $storeId = $user->store->store_id;

        $placed = \App\Models\Order::where('store_id', $storeId)->where('status', 'placed')->count();
        $preparing = \App\Models\Order::where('store_id', $storeId)->where('status', 'preparing')->count();
        $picked_up = \App\Models\Order::where('store_id', $storeId)->where('status', 'picked_up')->count();
        $cancelled = \App\Models\Order::where('store_id', $storeId)->where('status', 'cancelled')->count();

        $recentOrders = \App\Models\Order::with('consumer')
            ->where('store_id', $storeId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->order_id,
                    'date' => $order->created_at->format('M j, Y, g:i a'),
                    'customer' => $order->consumer->full_name ?? 'Unknown',
                    'avatar' => $order->consumer->profile_picture_url ?? null,
                    'price' => '₱' . number_format($order->total_amount, 2),
                    'status' => ucfirst(str_replace('_', ' ', $order->status))
                ];
            });

        return response()->json([
            'placed_orders' => $placed,
            'preparing_orders' => $preparing,
            'picked_up_orders' => $picked_up,
            'cancelled_orders' => $cancelled,
            'recent_orders' => $recentOrders
        ]);
    }

    /**
     * Get the authenticated vendor's profile and store information.
     *
     * GET /api/vendor/profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->load('store');
        }
        
        return response()->json($user);
    }

    /**
     * Get data for interactive revenue chart.
     *
     * GET /api/vendor/stats/chart
     */
    public function chartStats(Request $request)
    {
        $vendor = $request->user();
        if (!$vendor || !$vendor->store) {
            return response()->json([]);
        }
        $storeId = $vendor->store->store_id;
        $filter = $request->query('filter', 'Daily');

        $orders = \App\Models\Order::where('store_id', $storeId)
            ->where('status', 'picked_up')
            ->get();
            
        $data = [];

        if (strtolower($filter) === 'weekly') {
            $grouped = $orders->groupBy(function($date) {
                return \Carbon\Carbon::parse($date->updated_at)->startOfWeek()->format('M d, Y');
            });
            foreach ($grouped as $key => $items) {
                $data[] = ['period' => $key, 'total' => $items->sum('total_amount')];
            }
        } elseif (strtolower($filter) === 'monthly') {
            $grouped = $orders->groupBy(function($date) {
                return \Carbon\Carbon::parse($date->updated_at)->format('M Y');
            });
            foreach ($grouped as $key => $items) {
                $data[] = ['period' => $key, 'total' => $items->sum('total_amount')];
            }
        } else {
            $grouped = $orders->groupBy(function($date) {
                return \Carbon\Carbon::parse($date->updated_at)->format('M d');
            });
            foreach ($grouped as $key => $items) {
                $data[] = ['period' => $key, 'total' => $items->sum('total_amount')];
            }
        }

        // Sort chronologically using Carbon parse on period
        usort($data, function($a, $b) {
            return \Carbon\Carbon::parse($a['period'])->timestamp <=> \Carbon\Carbon::parse($b['period'])->timestamp;
        });

        // Limit results for visualization
        if (strtolower($filter) === 'weekly') {
            $data = array_slice($data, -8); // last 8 weeks
        } elseif (strtolower($filter) === 'monthly') {
            $data = array_slice($data, -6); // last 6 months
        } else {
            $data = array_slice($data, -7); // last 7 days
        }

        return response()->json(array_values($data));
    }

    /**
     * Upload and update the store image.
     *
     * POST /api/vendor/profile/store-image
     */
    public function uploadStoreImage(Request $request)
    {
        $request->validate([
            'store_picture' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $user = $request->user();
        if (!$user->store) {
            return response()->json(['message' => 'Vendor store not found.'], 404);
        }

        $store = $user->store;

        if ($request->hasFile('store_picture')) {
            $photoPath = $request->file('store_picture')->store('stores', 'public');

            // Update the store record with raw path to match registration
            $store->store_picture = $photoPath;
            $store->save();

            $storeUrl = asset('storage/' . $photoPath);

            return response()->json([
                'message' => 'Store image uploaded successfully',
                'store_picture_url' => $store->store_picture_url
            ]);
        }

        return response()->json(['message' => 'No image uploaded.'], 400);
    }

    public function exportInventoryReport(Request $request)
    {
        try {
            $user = auth()->user();
            $store = $user->store;
            
            // Fetch products with category relationship
            $products = \App\Models\Inventory::where('store_id', $store->store_id)->with('category')->get();
            

            // Calculate Summary
            $summary = [
                'total_products' => $products->count(),
                'available_products' => $products->where('status', 'active')->count(),
                'archived_products' => $products->where('status', 'archived')->count(),
                'inventory_value' => $products->where('status', 'active')->sum(function($prod) {
                    $stock = $prod->stock_quantity ?? $prod->quantity ?? $prod->stock ?? 0;
                    return $prod->price * $stock;
                })
            ];
            
            // Generate Static Map URL (Base64 encoded to avoid remote fetching issues in both DomPDF and html2canvas)
            $mapUrl = null;
            if ($store->latitude && $store->longitude) {
                $remoteUrl = "https://static-maps.yandex.ru/1.x/?ll={$store->longitude},{$store->latitude}&z=15&l=map&pt={$store->longitude},{$store->latitude},pm2rdm";
                try {
                    $opts = [
                        "http" => [
                            "method" => "GET",
                            "header" => "User-Agent: Mozilla/5.0\r\n"
                        ]
                    ];
                    $context = stream_context_create($opts);
                    $mapData = file_get_contents($remoteUrl, false, $context);
                    if ($mapData) {
                        $mapUrl = 'data:image/png;base64,' . base64_encode($mapData);
                    }
                } catch (\Exception $e) {
                    \Log::warning("Could not fetch map image for PDF export: " . $e->getMessage());
                }
            }
            
            $pdf = Pdf::loadView('pdf.inventory-report', [
                'store' => $store,
                'owner' => $user,
                'products' => $products,
                'summary' => $summary,
                'mapUrl' => $mapUrl,
                'date' => now()->setTimezone('Asia/Manila')->format('F d, Y h:i A'),
                'render_mode' => 'pdf'
            ]);
            
            // Use A4 Portrait and Enable Remote Images
            $pdf->setPaper('a4', 'portrait')
                ->setOption(['isRemoteEnabled' => true]);
            
            return $pdf->stream('inventory-report.pdf');
        } catch (\Exception $e) {
            \Log::error('PDF Export Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
        }
    }

    public function exportInventoryReportHtml(Request $request)
    {
        try {
            $user = auth()->user();
            $store = $user->store;
            
            // Fetch products with category relationship
            $products = \App\Models\Inventory::where('store_id', $store->store_id)->with('category')->get();
            

            // Calculate Summary
            $summary = [
                'total_products' => $products->count(),
                'available_products' => $products->where('status', 'active')->count(),
                'archived_products' => $products->where('status', 'archived')->count(),
                'inventory_value' => $products->where('status', 'active')->sum(function($prod) {
                    $stock = $prod->stock_quantity ?? $prod->quantity ?? $prod->stock ?? 0;
                    return $prod->price * $stock;
                })
            ];
            
            // Generate Static Map URL (Base64 encoded to avoid remote fetching issues in both DomPDF and html2canvas)
            $mapUrl = null;
            if ($store->latitude && $store->longitude) {
                $remoteUrl = "https://static-maps.yandex.ru/1.x/?ll={$store->longitude},{$store->latitude}&z=15&l=map&pt={$store->longitude},{$store->latitude},pm2rdm";
                try {
                    $opts = [
                        "http" => [
                            "method" => "GET",
                            "header" => "User-Agent: Mozilla/5.0\r\n"
                        ]
                    ];
                    $context = stream_context_create($opts);
                    $mapData = file_get_contents($remoteUrl, false, $context);
                    if ($mapData) {
                        $mapUrl = 'data:image/png;base64,' . base64_encode($mapData);
                    }
                } catch (\Exception $e) {
                    \Log::warning("Could not fetch map image for HTML export: " . $e->getMessage());
                }
            }
            
            $date = now()->setTimezone('Asia/Manila')->format('F d, Y h:i A');
            // In the PDF export we passed 'owner' => $user. I'll do the same to match the blade template.
            $owner = $user;
            $html = view('pdf.inventory-report', compact('store', 'owner', 'summary', 'products', 'mapUrl', 'date'))->with('render_mode', 'html')->render();

            return response()->json(['html' => $html]);
        } catch (\Exception $e) {
            \Log::error('HTML Export Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to generate HTML: ' . $e->getMessage()], 500);
        }
    }
}
