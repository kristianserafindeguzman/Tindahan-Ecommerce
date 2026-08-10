<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vendor Order List Report</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #334155; margin: 0; padding: 0; line-height: 1.5; }
        
        body { padding: 20px; }
        .page { position: relative; }
        
        /* Header */
        .header { display: table; width: 100%; border-bottom: 3px solid #bd2427; padding-bottom: 15px; margin-bottom: 25px; }
        .header-left { display: table-cell; vertical-align: bottom; }
        .header-right { display: table-cell; text-align: right; vertical-align: bottom; font-size: 10px; color: #64748b; }
        .brand { font-weight: 900; font-size: 22px; color: #bd2427; letter-spacing: -0.5px; text-transform: uppercase; margin-bottom: 4px; }
        .store-name { margin: 0 0 2px 0; color: #0f172a; font-size: 18px; font-weight: 700; text-transform: uppercase; }
        .report-title { margin: 0; color: #64748b; font-size: 11px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* Store Info Table */
        .info-layout { display: table; width: 100%; margin-bottom: 25px; }
        .info-cell { display: table-cell; width: 55%; vertical-align: top; padding-right: 20px; }
        .map-cell { display: table-cell; width: 45%; vertical-align: top; text-align: right; }
        
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px 0; border-bottom: 1px dashed #e2e8f0; vertical-align: top; }
        .info-table .label { font-weight: 700; color: #475569; width: 120px; font-size: 10px; text-transform: uppercase; }
        .info-table .val { color: #0f172a; font-weight: 500; }
        
        /* Map Box */
        .map-wrapper { display: inline-block; padding: 4px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; }
        .map-wrapper img { width: 240px; height: 160px; border-radius: 4px; display: block; }
        .map-attribution { font-size: 8px; color: #94a3b8; text-align: center; margin-top: 4px; font-style: italic; }
        
        /* Sections */
        .section-title { font-size: 12px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 25px; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        
        /* Data Table */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th, .data-table td { padding: 8px 8px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: middle; }
        .data-table th { background-color: #3f0909; color: #f8fafc; font-weight: 700; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #bd2427; }
        .data-table tbody tr:nth-child(even) { background-color: #f8fafc; }
        .data-table td { font-size: 10px; color: #1e293b; }
        
        /* Badges */
        .badge { padding: 3px 8px; border-radius: 4px; font-size: 8px; font-weight: 800; text-transform: uppercase; display: inline-block; line-height: 1; color: white; }
        .badge-placed { background: #2563eb; }
        .badge-preparing { background: #f59e0b; }
        .badge-ready_for_pickup { background: #f97316; }
        .badge-picked_up { background: #16a34a; }
        .badge-cancelled { background: #dc2626; }
        
        .empty-state { text-align: center; padding: 20px; font-size: 10px; color: #64748b; font-style: italic; background: #f8fafc; border: 1px dashed #cbd5e1; margin-bottom: 20px; }
        
        .page-number:before { content: "Page " counter(page); }
        
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
    </style>
</head>
<body>

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

    <div class="page">
        <!-- Main Header -->
        <div class="header">
            <div class="header-left">
                <div class="brand">Tindahan</div>
                <h2 class="store-name">{{ $store->store_name ?? 'Vendor Store' }}</h2>
                <h3 class="report-title">Order List Report</h3>
            </div>
            <div class="header-right">
                <div>Generated: {{ $date }}</div>
                <div>Owner: {{ $owner->full_name ?? 'N/A' }}</div>
                <div>Contact: {{ $owner->phone_number ?? 'N/A' }}</div>
            </div>
        </div>

        <div class="section-title" style="margin-top: 0;">Store Information</div>
        <div class="info-layout">
            <div class="info-cell">
                <table class="info-table">
                    <tr>
                        <td class="label">Address</td>
                        <td class="val">{{ $store->address ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Coordinates</td>
                        <td class="val" style="font-family: monospace; font-size: 9px;">{{ $store->latitude ?? 'N/A' }}, {{ $store->longitude ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
            <div class="map-cell">
                <div class="map-wrapper">
                    @if($mapUrl)
                        <img src="{{ $mapUrl }}" alt="Store Location Map" width="240" height="160">
                        <div class="map-attribution">&copy; OpenStreetMap contributors</div>
                    @else
                        <div style="width: 240px; height: 160px; display: table; background: #e2e8f0; border-radius: 4px;">
                            <span style="display: table-cell; vertical-align: middle; text-align: center; color: #64748b; font-style: italic; font-size: 10px;">Map unavailable<br>(No coordinates)</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @php
            $statuses = [
                'placed' => 'Placed',
                'preparing' => 'Preparing',
                'ready_for_pickup' => 'Ready for Pickup',
                'picked_up' => 'Picked Up',
                'cancelled' => 'Cancelled'
            ];
        @endphp

        @foreach($statuses as $key => $label)
            <div class="section-title">{{ $label }} Orders</div>
            
            @if(count($groupedOrders[$key]) > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th style="text-align: right;">Price (PHP)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groupedOrders[$key] as $order)
                            <tr>
                                <td><span style="font-weight: bold; color: #0f172a;">#{{ $order->order_id }}</span></td>
                                <td>{{ \Carbon\Carbon::parse($order->created_at)->format('M j, g:i A') }}</td>
                                <td>{{ $order->consumer->full_name ?? 'Unknown Customer' }}</td>
                                <td style="text-align: right; font-weight: bold;">{{ number_format($order->total_amount, 2) }}</td>
                                <td><span class="badge badge-{{ $key }}">{{ $label }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">No {{ strtolower($label) }} orders found.</div>
            @endif
        @endforeach

    </div>
</body>
</html>
