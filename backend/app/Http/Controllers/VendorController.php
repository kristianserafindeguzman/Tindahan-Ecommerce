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
                'available_products' => $products->where('status', 'active')->sum(function($prod) {
                    if (is_array($prod->variants) && count($prod->variants) > 0) {
                        return collect($prod->variants)->sum('quantity');
                    }
                    return $prod->stock_quantity ?? 0;
                }),
                'archived_products' => $products->where('status', 'archived')->count(),
                'inventory_value' => $products->where('status', 'active')->sum(function($prod) {
                    if (is_array($prod->variants) && count($prod->variants) > 0) {
                        return collect($prod->variants)->sum(function($v) {
                            return ($v['price'] ?? 0) * ($v['quantity'] ?? 0);
                        });
                    }
                    $stock = $prod->stock_quantity ?? 0;
                    return $prod->price * $stock;
                })
            ];
            
            // Generate Static Map URL using the centralized helper
            $mapUrl = $this->generateMapImage($store->latitude, $store->longitude);
            
            $pdf = Pdf::loadView('pdf.inventory-report', [
                'store' => $store,
                'owner' => $user,
                'products' => $products,
                'summary' => $summary,
                'mapUrl' => $mapUrl,
                'date' => now()->setTimezone('Asia/Manila')->format('F d, Y h:i A'),
                'render_mode' => 'pdf',
                'isPdf' => true
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
                'available_products' => $products->where('status', 'active')->sum(function($prod) {
                    if (is_array($prod->variants) && count($prod->variants) > 0) {
                        return collect($prod->variants)->sum('quantity');
                    }
                    return $prod->stock_quantity ?? 0;
                }),
                'archived_products' => $products->where('status', 'archived')->count(),
                'inventory_value' => $products->where('status', 'active')->sum(function($prod) {
                    if (is_array($prod->variants) && count($prod->variants) > 0) {
                        return collect($prod->variants)->sum(function($v) {
                            return ($v['price'] ?? 0) * ($v['quantity'] ?? 0);
                        });
                    }
                    $stock = $prod->stock_quantity ?? 0;
                    return $prod->price * $stock;
                })
            ];
            
            // Generate Static Map URL using the centralized helper
            $mapUrl = $this->generateMapImage($store->latitude, $store->longitude);
            
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

    /**
     * Helper to generate a statically stitched map image centered precisely on the vendor coordinates.
     */
    private function generateMapImage($lat, $lon)
    {
        if (!is_numeric($lat) || !is_numeric($lon)) {
            return null;
        }

        try {
            $zoom = 16;
            $width = 440;
            $height = 260;

            $n = pow(2, $zoom);
            $x_p = (($lon + 180) / 360) * $n;
            $y_p = (1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / pi()) / 2 * $n;

            $pixelX = $x_p * 256;
            $pixelY = $y_p * 256;

            $minX = $pixelX - $width / 2;
            $minY = $pixelY - $height / 2;
            $maxX = $pixelX + $width / 2;
            $maxY = $pixelY + $height / 2;

            $startTileX = (int) floor($minX / 256);
            $startTileY = (int) floor($minY / 256);
            $endTileX = (int) floor($maxX / 256);
            $endTileY = (int) floor($maxY / 256);

            $canvas = imagecreatetruecolor($width, $height);
            $bg = imagecolorallocate($canvas, 248, 250, 252);
            imagefill($canvas, 0, 0, $bg);

            $osmFailed = false;

            for ($x = $startTileX; $x <= $endTileX; $x++) {
                if ($osmFailed) break;
                for ($y = $startTileY; $y <= $endTileY; $y++) {
                    if ($osmFailed) break;
                    $tileUrl = "https://tile.openstreetmap.org/{$zoom}/{$x}/{$y}.png";
                    try {
                        $response = \Illuminate\Support\Facades\Http::timeout(1.5)
                            ->withHeaders(['User-Agent' => 'TindahanApp/1.0'])
                            ->get($tileUrl);
                        $tileData = $response->successful() ? $response->body() : false;
                    } catch (\Exception $e) {
                        $tileData = false;
                        $osmFailed = true; // Abort further tiles if OSM is unreachable
                    }
                    if ($tileData) {
                        $img = @imagecreatefromstring($tileData);
                        if ($img) {
                            $destX = (int) round(($x * 256) - $minX);
                            $destY = (int) round(($y * 256) - $minY);
                            imagecopy($canvas, $img, $destX, $destY, 0, 0, 256, 256);
                            imagedestroy($img);
                        }
                    }
                }
            }

            // Draw Pin at Center
            $centerX = (int) round($width / 2);
            $centerY = (int) round($height / 2);

            $red = imagecolorallocate($canvas, 189, 36, 39); // Tindahan Red #bd2427
            $dark = imagecolorallocate($canvas, 15, 23, 42); // #0f172a
            $white = imagecolorallocate($canvas, 255, 255, 255);

            // Shadow
            imagefilledellipse($canvas, $centerX, $centerY + 2, 16, 6, imagecolorallocatealpha($canvas, 0, 0, 0, 80));

            // Pin styling
            $r = 12;
            $pinY = $centerY - 16;

            // Pin Triangle
            $points = [
                (int) round($centerX - $r * 0.8), (int) round($pinY + $r * 0.4),
                (int) round($centerX + $r * 0.8), (int) round($pinY + $r * 0.4),
                $centerX, $centerY
            ];
            imagefilledpolygon($canvas, $points, $red);
            imagefilledellipse($canvas, $centerX, $pinY, $r * 2, $r * 2, $red);
            imagefilledellipse($canvas, $centerX, $pinY, $r, $r, $white);

            ob_start();
            imagepng($canvas);
            $pngData = ob_get_clean();
            imagedestroy($canvas);

            return 'data:image/png;base64,' . base64_encode($pngData);

        } catch (\Exception $e) {
            \Log::warning("Could not fetch map image for export: " . $e->getMessage());
            return null;
        }
    }
}
