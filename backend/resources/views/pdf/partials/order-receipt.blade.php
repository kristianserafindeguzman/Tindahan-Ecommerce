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
                            {{ $item->inventory->product_name ?? 'Unknown Product' }}
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
            <div>This is a transaction acknowledgment,</div>
            <div>not a BIR-registered official receipt.</div>
            <div style="margin-top: 5px;">Tindahan E-Commerce</div>
        </div>
    </div>
