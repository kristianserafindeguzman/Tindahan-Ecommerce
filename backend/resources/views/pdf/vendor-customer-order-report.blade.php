<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer Order Report</title>
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
        
        /* Two Column Layout */
        .columns { display: table; width: 100%; margin-bottom: 25px; }
        .col-left { display: table-cell; width: 50%; vertical-align: top; padding-right: 20px; }
        .col-right { display: table-cell; width: 50%; vertical-align: top; padding-left: 20px; }

        /* Store Info Table */
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px 0; border-bottom: 1px dashed #e2e8f0; vertical-align: top; }
        .info-table .label { font-weight: 700; color: #475569; width: 100px; font-size: 10px; text-transform: uppercase; }
        .info-table .val { color: #0f172a; font-weight: 500; }
        
        /* Sections */
        .section-title { font-size: 12px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 15px; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
        
        /* Data Table */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th, .data-table td { padding: 8px 8px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: middle; }
        .data-table th { background-color: #3f0909; color: #f8fafc; font-weight: 700; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #bd2427; }
        .data-table tbody tr:nth-child(even) { background-color: #f8fafc; }
        .data-table td { font-size: 10px; color: #1e293b; }
        
        /* Totals Table */
        .totals-table { width: 40%; margin-left: auto; border-collapse: collapse; }
        .totals-table td { padding: 6px 8px; font-size: 11px; }
        .totals-table .totals-label { font-weight: 600; color: #64748b; text-align: right; }
        .totals-table .totals-val { font-weight: 700; color: #0f172a; text-align: right; }
        .totals-table .grand-total td { font-size: 14px; font-weight: 800; color: #bd2427; border-top: 2px solid #e2e8f0; padding-top: 10px; }

        /* Badges */
        .badge { padding: 4px 10px; border-radius: 4px; font-size: 9px; font-weight: 800; text-transform: uppercase; display: inline-block; line-height: 1; color: white; }
        .badge-placed { background: #2563eb; }
        .badge-preparing { background: #f59e0b; }
        .badge-ready_for_pickup { background: #f97316; }
        .badge-picked_up { background: #16a34a; }
        .badge-cancelled { background: #dc2626; }
        
        /* Receipt */
        .receipt-page { width: 300px; margin: 0 auto; font-family: monospace; font-size: 12px; color: #000; padding: 20px; border: 1px dashed #ccc; background: #fff; page-break-before: always; }
        .receipt-header { text-align: center; margin-bottom: 20px; }
        .receipt-title { font-weight: bold; font-size: 16px; text-transform: uppercase; margin-bottom: 5px; }
        .receipt-divider { border-top: 1px dashed #000; margin: 10px 0; }
        .receipt-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .receipt-table th, .receipt-table td { padding: 2px 0; vertical-align: top; }
        .receipt-table th { border-bottom: 1px dashed #000; text-align: left; }
        .receipt-totals { width: 100%; margin-top: 10px; }
        .receipt-totals td { padding: 2px 0; }
        
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
                <h3 class="report-title">Customer Order Details</h3>
            </div>
            <div class="header-right">
                <div>Generated: {{ $date }}</div>
                <div>Owner: {{ $owner->full_name ?? 'N/A' }}</div>
                <div>Contact: {{ $owner->phone_number ?? 'N/A' }}</div>
            </div>
        </div>

        <div class="columns">
            <div class="col-left">
                <div class="section-title" style="margin-top: 0;">Order Information</div>
                <table class="info-table">
                    <tr>
                        <td class="label">Order ID</td>
                        <td class="val" style="font-weight: bold; color: #bd2427;">#{{ $order->order_id }}</td>
                    </tr>
                    <tr>
                        <td class="label">Status</td>
                        <td class="val">
                            @php
                                $statusLabels = [
                                    'placed' => 'Placed',
                                    'preparing' => 'Preparing',
                                    'ready_for_pickup' => 'Ready for Pickup',
                                    'picked_up' => 'Picked Up',
                                    'cancelled' => 'Cancelled'
                                ];
                                $sLabel = $statusLabels[$order->status] ?? ucfirst($order->status);
                            @endphp
                            <span class="badge badge-{{ $order->status }}">{{ $sLabel }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Order Date</td>
                        <td class="val">{{ \Carbon\Carbon::parse($order->created_at)->format('F d, Y h:i A') }}</td>
                    </tr>
                    @if($order->status == 'picked_up' && $order->updated_at)
                    <tr>
                        <td class="label">Completed Date</td>
                        <td class="val">{{ \Carbon\Carbon::parse($order->updated_at)->format('F d, Y h:i A') }}</td>
                    </tr>
                    @endif
                </table>
            </div>
            <div class="col-right">
                <div class="section-title" style="margin-top: 0;">Customer Information</div>
                <table class="info-table">
                    <tr>
                        <td class="label">Name</td>
                        <td class="val">{{ $order->consumer->full_name ?? 'Unknown' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Email</td>
                        <td class="val">{{ $order->consumer->email ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Contact</td>
                        <td class="val">{{ $order->consumer->phone_number ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @if($order->status === 'cancelled')
        <div class="section-title" style="color: #dc2626; border-bottom-color: #e2e8f0;">Cancellation Reason</div>
        <div style="margin-bottom: 25px; color: #0f172a; font-weight: 500;">
            {{ $order->cancellation_reason ? $order->cancellation_reason : 'No cancellation reason provided.' }}
        </div>
        @endif

        <div class="section-title">Order Items</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Unit Price (PHP)</th>
                    <th style="text-align: right;">Subtotal (PHP)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>
                            <div style="font-weight: bold;">{{ $item->product_name }}</div>
                            @if($item->variant_name)
                                <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Variant: {{ $item->variant_name }}</div>
                            @endif
                        </td>
                        <td style="text-align: center; font-weight: bold;">{{ $item->quantity }}</td>
                        <td style="text-align: right;">{{ number_format($item->price, 2) }}</td>
                        <td style="text-align: right; font-weight: bold;">{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals-table">
            <tr>
                <td class="totals-label">Subtotal</td>
                <td class="totals-val">{{ number_format($order->total_amount, 2) }}</td>
            </tr>
            <tr class="grand-total">
                <td class="totals-label">Total Amount</td>
                <td class="totals-val">PHP {{ number_format($order->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($order->status === 'picked_up')
    <div class="receipt-page">
        <div class="receipt-header">
            <div class="receipt-title">{{ $store->store_name }}</div>
            <div>{{ $store->address ?? 'N/A' }}</div>
            <div>{{ $owner->phone_number ?? 'N/A' }}</div>
            <div class="receipt-divider"></div>
            <div style="text-align: left; font-size: 11px;">
                <div>Date: {{ \Carbon\Carbon::parse($order->updated_at)->format('Y-m-d H:i') }}</div>
                <div>Order #: {{ $order->order_id }}</div>
                <div>Customer: {{ $order->consumer->full_name ?? 'Guest' }}</div>
            </div>
            <div class="receipt-divider"></div>
        </div>
        
        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Qty</th>
                    <th>Item</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->quantity }}</td>
                        <td>
                            {{ $item->product_name }}
                            @if($item->variant_name)
                                <br><small style="color: #555;">({{ $item->variant_name }})</small>
                            @endif
                        </td>
                        <td style="text-align: right;">{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="receipt-divider"></div>
        <table class="receipt-totals">
            <tr>
                <td style="text-align: right; font-weight: bold;">TOTAL:</td>
                <td style="text-align: right; font-weight: bold;">PHP {{ number_format($order->total_amount, 2) }}</td>
            </tr>
            <tr>
                <td style="text-align: right;">PAID:</td>
                <td style="text-align: right;">PHP {{ number_format($order->total_amount, 2) }}</td>
            </tr>
        </table>
        <div class="receipt-divider"></div>
        
        <div style="text-align: center; margin-top: 20px;">
            <div>Thank you for your purchase!</div>
            <div>Tindahan E-Commerce</div>
        </div>
    </div>
    @endif
</body>
</html>
