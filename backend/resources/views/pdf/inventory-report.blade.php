<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inventory Report</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 20px; line-height: 1.4; }
        .header { display: table; width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 20px; }
        .header-left { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; text-align: right; vertical-align: middle; font-size: 10px; color: #64748b; }
        .header h1 { margin: 0; color: #0f172a; font-size: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
        .header h3 { margin: 3px 0 0 0; color: #475569; font-size: 12px; font-weight: normal; }
        
        .section-title { background: #f8fafc; color: #334155; padding: 6px 10px; font-weight: bold; border-left: 4px solid #2563eb; margin-bottom: 10px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 4px 6px; vertical-align: top; }
        .info-table .label { font-weight: bold; color: #64748b; width: 110px; }
        .info-table .val { color: #0f172a; }
        
        .map-box { text-align: right; }
        .map-box img { border: 1px solid #cbd5e1; border-radius: 6px; width: 220px; height: 130px; object-fit: cover; }
        
        .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; text-align: center; }
        .summary-table td { border: 1px solid #e2e8f0; padding: 8px; width: 25%; background: #fdfdfd; }
        .summary-table .title-cell { font-size: 10px; color: #64748b; text-transform: uppercase; }
        .summary-table .val-cell { font-size: 15px; font-weight: bold; color: #2563eb; display: block; margin-top: 4px; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .data-table th, .data-table td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; }
        .data-table th { background-color: #0f172a; color: white; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .data-table td { font-size: 11px; color: #334155; }
        .data-table tr:nth-child(even) { background-color: #f8fafc; }
        
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-archived { background: #fee2e2; color: #991b1b; }

        @if(($render_mode ?? 'pdf') === 'pdf')
        .footer { position: fixed; bottom: -20px; left: 0; right: 0; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 5px; }
        @else
        .footer { margin-top: 40px; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 5px; }
        @endif
        .page-number:before { content: "Page " counter(page); }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-left">
            <h1>{{ $store->store_name ?? 'Store Inventory' }}</h1>
            <h3>Vendor Inventory Management Report</h3>
        </div>
        <div class="header-right">
            Generated: {{ $date }}
        </div>
    </div>

    <div class="section-title">Store & Location Profile</div>
    <table class="info-table">
        <tr>
            <td style="width: 55%;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td class="label">Store Owner:</td>
                        <td class="val">{{ $owner->full_name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Operating Schedule:</td>
                        <td class="val">
                            @if(is_array($store->operating_days))
                                {{ implode(', ', $store->operating_days) }}
                            @elseif(is_string($store->operating_days))
                                @php
                                    $decodedDays = json_decode($store->operating_days, true);
                                    echo is_array($decodedDays) ? implode(', ', $decodedDays) : $store->operating_days;
                                @endphp
                            @else
                                {{ $store->operating_days ?? 'N/A' }}
                            @endif
                            | {{ $store->opening_time ? date('h:i A', strtotime($store->opening_time)) : 'N/A' }} - {{ $store->closing_time ? date('h:i A', strtotime($store->closing_time)) : 'N/A' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Address:</td>
                        <td class="val">{{ $store->address ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Location Coordinates:</td>
                        <td class="val">{{ $store->latitude ?? 'N/A' }}, {{ $store->longitude ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Contact Number:</td>
                        <td class="val">{{ $owner->phone_number ?? 'N/A' }}</td>
                    </tr>
                </table>
            </td>
            <td class="map-box" style="width: 45%;">
                @if($mapUrl)
                    <img src="{{ $mapUrl }}" alt="Store Location Map">
                    <div style="font-size: 8px; color: #64748b; margin-top: 2px;">&copy; OpenStreetMap contributors</div>
                @else
                    <div style="font-style: italic; color: #94a3b8; padding: 40px; border: 1px dashed #cbd5e1;">Map unavailable (No coordinates)</div>
                @endif
            </td>
        </tr>
    </table>

    <div style="display: table; width: 100%; margin-bottom: 20px; table-layout: fixed;">
        <div style="display: table-cell; width: 32%; border: 1px dashed #cbd5e1; background: #f8fafc; padding: 10px; text-align: center; border-radius: 6px;">
            <strong style="font-size: 10px; color: #0f172a; display: block; margin-bottom: 4px;">[ Restock Alert Analysis ]</strong>
            <span style="font-size: 9px; color: #64748b;">Monitoring low stock thresholds & inventory velocity.</span>
        </div>
        <div style="display: table-cell; width: 2%;"></div>
        <div style="display: table-cell; width: 32%; border: 1px dashed #cbd5e1; background: #f8fafc; padding: 10px; text-align: center; border-radius: 6px;">
            <strong style="font-size: 10px; color: #0f172a; display: block; margin-bottom: 4px;">[ Upcoming Trend Predictions ]</strong>
            <span style="font-size: 9px; color: #64748b;">Forecasting seasonal demand increases.</span>
        </div>
        <div style="display: table-cell; width: 2%;"></div>
        <div style="display: table-cell; width: 32%; border: 1px dashed #cbd5e1; background: #f8fafc; padding: 10px; text-align: center; border-radius: 6px;">
            <strong style="font-size: 10px; color: #0f172a; display: block; margin-bottom: 4px;">[ Top Performance Metrics ]</strong>
            <span style="font-size: 9px; color: #64748b;">Highlighting top revenue-driving products.</span>
        </div>
    </div>

    <div class="section-title">Inventory Performance Summary</div>
    <table class="summary-table">
        <tr>
            <td>
                <span class="title-cell">Total Products</span>
                <span class="val-cell">{{ $summary['total_products'] }}</span>
            </td>
            <td>
                <span class="title-cell">Available Stock</span>
                <span class="val-cell" style="color: #166534;">{{ $summary['available_products'] }}</span>
            </td>
            <td>
                <span class="title-cell">Archived Items</span>
                <span class="val-cell" style="color: #991b1b;">{{ $summary['archived_products'] }}</span>
            </td>
            <td>
                <span class="title-cell">Est. Inventory Value</span>
                <span class="val-cell">PHP {{ number_format($summary['inventory_value'], 2) }}</span>
            </td>
        </tr>
    </table>

    <div class="section-title">Detailed Product Catalog</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Photo</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Unit Price</th>
                <th>Stock Qty</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                @php
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
                @endphp
                
                @if(is_array($product->variants) && count($product->variants) > 0)
                    @foreach($product->variants as $variant)
                        <tr>
                            <td style="padding: 6px; text-align: center;">
                                @if($imageSource)
                                    <img src="{{ $imageSource }}" style="width: 32px; height: 32px; object-fit: cover; border-radius: 4px;" crossorigin="anonymous" />
                                @else
                                    <div style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background-color: #f1f5f9; border-radius: 4px; border: 1px dashed #cbd5e1;">
                                        <span style="color: #94a3b8; font-size: 8px; line-height: 1; text-align: center;">No<br>Img</span>
                                    </div>
                                @endif
                            </td>
                            <td style="font-weight: bold;">{{ $product->product_name ?? $product->name }} <br><span style="color: #475569; font-weight: normal; font-size: 9px;">Variant: {{ $variant['name'] ?? 'Variant' }}</span></td>
                            <td>{{ $product->category->category_name ?? $product->category->name ?? 'N/A' }}</td>
                            <td>PHP {{ number_format($variant['price'] ?? 0, 2) }}</td>
                            <td>{{ $variant['quantity'] ?? 0 }}</td>
                            <td>{!! $statusBadge !!}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td style="padding: 6px; text-align: center;">
                            @if($imageSource)
                                <img src="{{ $imageSource }}" style="width: 32px; height: 32px; object-fit: cover; border-radius: 4px;" crossorigin="anonymous" />
                            @else
                                <div style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background-color: #f1f5f9; border-radius: 4px; border: 1px dashed #cbd5e1;">
                                    <span style="color: #94a3b8; font-size: 8px; line-height: 1; text-align: center;">No<br>Img</span>
                                </div>
                            @endif
                        </td>
                        <td style="font-weight: bold;">{{ $product->product_name ?? $product->name }}</td>
                        <td>{{ $product->category->category_name ?? $product->category->name ?? 'N/A' }}</td>
                        <td>PHP {{ number_format($product->price, 2) }}</td>
                        <td>{{ $product->stock_quantity ?? $product->quantity ?? $product->stock ?? 0 }}</td>
                        <td>{!! $statusBadge !!}</td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #94a3b8; padding: 20px;">No products found in this inventory.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <table style="width: 100%;">
            <tr>
                <td>Tindahan E-Commerce System &bull; Official Vendor Inventory Management Report</td>
                <td style="text-align: right;" class="page-number"></td>
            </tr>
        </table>
    </div>

</body>
</html>
