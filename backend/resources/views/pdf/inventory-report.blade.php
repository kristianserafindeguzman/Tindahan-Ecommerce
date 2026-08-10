<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vendor Inventory Management Report</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #334155; margin: 0; padding: 0; line-height: 1.5; }
        
        /* Page Styling */
        @if(($render_mode ?? 'pdf') === 'pdf')
            body { padding: 20px; }
            .page {
                page-break-after: always;
                position: relative;
            }
            .page:last-child {
                page-break-after: auto;
            }
            .footer { position: fixed; bottom: -20px; left: 0; right: 0; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
        @else
            body { background: #f1f5f9; padding: 20px; }
            .page {
                width: 794px;
                min-height: 1123px;
                padding: 40px;
                background: white;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                margin: 0 auto 20px auto;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                position: relative;
            }
            .footer { margin-top: auto; padding-top: 20px; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
        @endif

        /* Header */
        .header { display: table; width: 100%; border-bottom: 3px solid #bd2427; padding-bottom: 15px; margin-bottom: 25px; }
        .header-left { display: table-cell; vertical-align: bottom; }
        .header-right { display: table-cell; text-align: right; vertical-align: bottom; font-size: 10px; color: #64748b; }
        .brand { font-weight: 900; font-size: 22px; color: #bd2427; letter-spacing: -0.5px; text-transform: uppercase; margin-bottom: 4px; }
        .store-name { margin: 0 0 2px 0; color: #0f172a; font-size: 18px; font-weight: 700; text-transform: uppercase; }
        .report-title { margin: 0; color: #64748b; font-size: 11px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* Sections */
        .section-title { font-size: 12px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        
        /* Store Info Table */
        .info-layout { display: table; width: 100%; margin-bottom: 25px; }
        .info-cell { display: table-cell; width: 55%; vertical-align: top; padding-right: 20px; }
        .map-cell { display: table-cell; width: 45%; vertical-align: top; text-align: right; }
        
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px 0; border-bottom: 1px dashed #e2e8f0; vertical-align: top; }
        .info-table .label { font-weight: 700; color: #475569; width: 120px; font-size: 10px; text-transform: uppercase; }
        .info-table .val { color: #0f172a; font-weight: 500; }
        
        /* Operating Hours nested table */
        .hours-table { width: 100%; border-collapse: collapse; }
        .hours-table td { border-bottom: none; padding: 2px 0; font-size: 10px; }
        .day-col { width: 75px; font-weight: 500; color: #475569; }
        
        /* Map Box */
        .map-wrapper { display: inline-block; padding: 4px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; }
        .map-wrapper img { width: 240px; height: 160px; border-radius: 4px; display: block; }
        .map-attribution { font-size: 8px; color: #94a3b8; text-align: center; margin-top: 4px; font-style: italic; }
        
        /* Summary Cards */
        .summary-container { display: table; width: 100%; margin-bottom: 30px; table-layout: fixed; border-collapse: separate; border-spacing: 12px 0; margin-left: -12px; }
        .summary-card { display: table-cell; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 15px; }
        .summary-card.highlight { border-color: #fecdd3; background: #fff1f2; }
        .summary-title { font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 4px; display: block; }
        .summary-card.highlight .summary-title { color: #9f1239; }
        .summary-value { font-size: 18px; font-weight: 800; color: #0f172a; }
        .summary-value.green { color: #15803d; }
        .summary-value.red { color: #b91c1c; }
        
        /* Data Table */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .data-table th, .data-table td { padding: 10px 8px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: middle; }
        .data-table th { background-color: #3f0909; color: #f8fafc; font-weight: 700; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #bd2427; }
        .data-table tbody tr:nth-child(even) { background-color: #f8fafc; }
        .data-table td { font-size: 10px; color: #1e293b; }
        
        /* Badges */
        .badge { padding: 3px 8px; border-radius: 4px; font-size: 8px; font-weight: 800; text-transform: uppercase; display: inline-block; line-height: 1; }
        .badge-active { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-archived { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        
        .page-number:before { content: "Page " counter(page); }
        
        @if(($render_mode ?? 'pdf') === 'pdf')
            /* PDF Footer fixed at bottom of every page */
            footer {
                position: fixed; 
                bottom: -20px; 
                left: 0px; 
                right: 0px;
                height: 30px; 
                font-size: 9px; 
                color: #94a3b8; 
                border-top: 1px solid #e2e8f0; 
                padding-top: 8px;
            }
        @endif
    </style>
</head>
<body>

@if(($render_mode ?? 'pdf') === 'pdf')
    <!-- Fixed footer repeated on every page by DomPDF -->
    <footer>
        <table style="width: 100%; border: none;">
            <tr>
                <td style="text-align: left; border: none; padding: 0;">Tindahan Vendor Management System</td>
                <td style="text-align: right; border: none; padding: 0;">
                    <span class="page-number"></span>
                </td>
            </tr>
        </table>
    </footer>
@endif

@php
    // PART 3 & 4 - Flatten export rows to guarantee data completeness and correct variant representation
    $flatRows = [];
    foreach($products as $product) {
        if(is_array($product->variants) && count($product->variants) > 0) {
            foreach($product->variants as $variant) {
                $flatRows[] = [
                    'is_variant' => true,
                    'product' => $product,
                    'variant' => $variant
                ];
            }
        } else {
            $flatRows[] = [
                'is_variant' => false,
                'product' => $product
            ];
        }
    }

    $pages = [];
    
    // Decouple PDF pagination from HTML pagination
    if (($render_mode ?? 'pdf') === 'pdf') {
        // PDF mode: Pass all rows as a single continuous list. DomPDF will naturally paginate the <table> and repeat the <thead>!
        $pages[] = $flatRows;
    } else {
        // HTML mode: Chunk manually so html2canvas can capture individual pages for the separate PNG downloads
        $currentPage = [];
        $page1Max = 12; // Adjusted since variants are now flattened 1:1 rows
        $pageNMax = 22;
        
        foreach($flatRows as $row) {
            $max = count($pages) === 0 ? $page1Max : $pageNMax;
            
            if(count($currentPage) >= $max) {
                $pages[] = $currentPage;
                $currentPage = [];
            }
            
            $currentPage[] = $row;
        }
        if(count($currentPage) > 0 || count($pages) === 0) {
            $pages[] = $currentPage;
        }
    }
@endphp

@foreach($pages as $pageIndex => $pageProducts)
<div class="page">
    @if($pageIndex === 0)
        <!-- Page 1 Header and Info -->
        <div class="header">
            <div class="header-left">
                <div class="brand">Tindahan</div>
                <h1 class="store-name">{{ $store->store_name ?? 'Vendor Store' }}</h1>
                <h3 class="report-title">Inventory Management Report</h3>
            </div>
            <div class="header-right">
                Generated: <strong>{{ $date }}</strong><br>
                By: {{ $owner->full_name ?? 'System' }}
            </div>
        </div>

        <div class="info-layout">
            <div class="info-cell">
                <div class="section-title">Store & Location Profile</div>
                <table class="info-table">
                    <tr>
                        <td class="label">Store Owner</td>
                        <td class="val">{{ $owner->full_name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Contact Number</td>
                        <td class="val">{{ $owner->phone_number ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Address</td>
                        <td class="val">{{ $store->address ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Coordinates</td>
                        <td class="val" style="font-family: monospace; font-size: 9px;">{{ $store->latitude ?? 'N/A' }}, {{ $store->longitude ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Operating Hours</td>
                        <td class="val">
                            <table class="hours-table">
                                @php
                                    $days = $store->operating_days;
                                    if (is_string($days)) {
                                        $days = json_decode($days, true);
                                    }
                                    if (is_array($days) && count($days) > 0) {
                                        $allDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                        
                                        $isLegacy = (array_keys($days) === range(0, count($days) - 1));
                                        
                                        foreach ($allDays as $dayName) {
                                            $isOpen = false;
                                            $open = $store->opening_time ? date('h:i A', strtotime($store->opening_time)) : 'N/A';
                                            $close = $store->closing_time ? date('h:i A', strtotime($store->closing_time)) : 'N/A';
                                            $isSet = false;

                                            if ($isLegacy) {
                                                $isSet = true;
                                                foreach ($days as $d) {
                                                    if (is_string($d) && strtolower(substr($d, 0, 3)) === strtolower(substr($dayName, 0, 3))) {
                                                        $isOpen = true;
                                                        break;
                                                    }
                                                }
                                            } else {
                                                if (isset($days[$dayName])) {
                                                    $isSet = true;
                                                    $schedule = $days[$dayName];
                                                    $isOpen = !empty($schedule['is_open']);
                                                    if ($isOpen) {
                                                        $open = !empty($schedule['opening_time']) ? date('h:i A', strtotime($schedule['opening_time'])) : $open;
                                                        $close = !empty($schedule['closing_time']) ? date('h:i A', strtotime($schedule['closing_time'])) : $close;
                                                    }
                                                }
                                            }

                                            if ($isSet) {
                                                if ($isOpen) {
                                                    echo "<tr><td class='day-col'>$dayName</td><td>$open - $close</td></tr>";
                                                } else {
                                                    echo "<tr><td class='day-col'>$dayName</td><td style='color: #94a3b8; font-style: italic;'>Closed</td></tr>";
                                                }
                                            } else {
                                                echo "<tr><td class='day-col'>$dayName</td><td style='color: #94a3b8; font-style: italic;'>Not Set</td></tr>";
                                            }
                                        }
                                    } else {
                                        echo "<tr><td>Schedule not configured.</td></tr>";
                                    }
                                @endphp
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="map-cell">
                <div style="height: 25px;"></div>
                <div class="map-wrapper">
                    @if($mapUrl)
                        <img src="{{ $mapUrl }}" alt="Store Location Map" width="240" height="160" crossorigin="anonymous">
                        <div class="map-attribution">&copy; OpenStreetMap contributors</div>
                    @else
                        <div style="width: 240px; height: 160px; display: table; background: #e2e8f0; border-radius: 4px;">
                            <span style="display: table-cell; vertical-align: middle; text-align: center; color: #64748b; font-style: italic; font-size: 10px;">Map unavailable<br>(No coordinates)</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="summary-container" style="margin-bottom: 25px;">
            <div class="summary-card">
                <span class="summary-title">Restock Alert</span>
                <span class="summary-value" style="font-size: 14px;">Analyzing Inventory...</span>
                <span style="display: block; font-size: 9px; color: #64748b; margin-top: 6px;">Predicted stock-out in N/A days</span>
            </div>
            <div class="summary-card">
                <span class="summary-title">Upcoming Trend</span>
                <span class="summary-value" style="font-size: 14px;">Gathering Data...</span>
                <span style="display: block; font-size: 9px; color: #64748b; margin-top: 6px;">Expected 0x demand increase</span>
            </div>
            <div class="summary-card">
                <span class="summary-title">Top Performance</span>
                <span class="summary-value" style="font-size: 14px;">Calculating...</span>
                <span style="display: block; font-size: 9px; color: #64748b; margin-top: 6px;">Highest revenue driver this week</span>
            </div>
        </div>

        <div class="section-title">Inventory Performance Summary</div>
        <div class="summary-container">
            <div class="summary-card">
                <span class="summary-title">Total Products</span>
                <span class="summary-value">{{ $summary['total_products'] }}</span>
            </div>
            <div class="summary-card">
                <span class="summary-title">Available Stock</span>
                <span class="summary-value green">{{ $summary['available_products'] }}</span>
            </div>
            <div class="summary-card">
                <span class="summary-title">Archived Items</span>
                <span class="summary-value red">{{ $summary['archived_products'] }}</span>
            </div>
            <div class="summary-card highlight">
                <span class="summary-title">Est. Inventory Value</span>
                <span class="summary-value" style="color: #bd2427;">PHP {{ number_format($summary['inventory_value'], 2) }}</span>
            </div>
        </div>
        
        <div class="section-title">Detailed Product Catalog</div>
    @else
        <!-- Subsequent Pages Mini Header -->
        <div class="header" style="border-bottom: 2px solid #bd2427; padding-bottom: 10px; margin-bottom: 20px;">
            <div class="header-left">
                <h3 class="report-title" style="color: #0f172a;">{{ $store->store_name ?? 'Vendor Store' }}</h3>
                <h3 class="report-title" style="font-size: 9px; font-weight: normal; margin-top: 2px;">Inventory Management Report - Page {{ $pageIndex + 1 }}</h3>
            </div>
        </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 45px; text-align: center;">Photo</th>
                <th>Product Information</th>
                <th>Category</th>
                <th style="text-align: right;">Unit Price</th>
                <th style="text-align: right;">Stock Qty</th>
                <th style="text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pageProducts as $row)
                @php
                    $product = $row['product'];
                    $isVariant = $row['is_variant'];
                    $variant = $row['variant'] ?? null;
                    
                    $pic = $product->product_picture;
                    $imageSource = null;
                    if ($pic) {
                        $relativePath = parse_url($pic, PHP_URL_PATH);
                        if (str_starts_with($relativePath, 'seed-images/')) {
                            $localPath = public_path($relativePath);
                        } else {
                            if (!str_starts_with($relativePath, '/storage') && !str_starts_with($relativePath, 'storage')) {
                                $relativePath = 'storage/' . ltrim($relativePath, '/');
                            }
                            $localPath = public_path(ltrim($relativePath, '/'));
                        }
                        
                        $renderMode = $render_mode ?? 'pdf';
                        if (file_exists($localPath)) {
                            if ($renderMode === 'pdf') {
                                $imageSource = $localPath;
                            } else {
                                $ext = pathinfo($localPath, PATHINFO_EXTENSION);
                                $data = file_get_contents($localPath);
                                $imageSource = 'data:image/' . $ext . ';base64,' . base64_encode($data);
                            }
                        }
                    }
                    $status = strtolower($product->status ?? 'active');
                    $badgeClass = $status === 'active' ? 'badge-active' : 'badge-archived';
                    $statusBadge = '<span class="badge ' . $badgeClass . '">' . ucfirst($status) . '</span>';
                    
                    $price = $isVariant ? ($variant['price'] ?? 0) : ($product->price ?? 0);
                    $quantity = $isVariant ? ($variant['quantity'] ?? 0) : ($product->stock_quantity ?? 0);
                @endphp
                
                <tr>
                    <td style="text-align: center; background: #ffffff;">
                        @if($imageSource)
                            <img src="{{ $imageSource }}" style="width: 36px; height: 36px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1;" crossorigin="anonymous" />
                        @else
                            <div style="width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; background-color: #f1f5f9; border-radius: 4px; border: 1px dashed #cbd5e1; margin: 0 auto;">
                                <span style="color: #94a3b8; font-size: 8px; line-height: 1; text-align: center;">No<br>Img</span>
                            </div>
                        @endif
                    </td>
                    <td>
                        <strong style="color: #0f172a; font-size: 11px;">{{ $product->product_name ?? $product->name }}</strong>
                        @if($isVariant)
                            <div style="color: #64748b; font-size: 9px; margin-top: 2px;">Variant: <strong style="color: #475569;">{{ $variant['name'] ?? 'N/A' }}</strong></div>
                        @endif
                    </td>
                    <td>{{ $product->category->category_name ?? $product->category->name ?? 'N/A' }}</td>
                    <td style="text-align: right; font-family: monospace; font-size: 11px;">PHP {{ number_format($price, 2) }}</td>
                    <td style="text-align: right; font-weight: 700;">{{ $quantity }}</td>
                    <td style="text-align: center;">{!! $statusBadge !!}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: #64748b; font-style: italic;">
                        No products found in the inventory.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- We render the footer statically at the end of each page div for HTML mode -->
    @if(($render_mode ?? 'pdf') !== 'pdf')
        <div class="footer">
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="text-align: left; border: none; padding: 0;">Tindahan Vendor Management System</td>
                    <td style="text-align: right; border: none; padding: 0;">
                        Page {{ $pageIndex + 1 }} of {{ count($pages) }}
                    </td>
                </tr>
            </table>
        </div>
    @endif
</div>
@endforeach

</body>
</html>
