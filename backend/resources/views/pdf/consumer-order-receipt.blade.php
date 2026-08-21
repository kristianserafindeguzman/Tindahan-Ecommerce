<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Receipt</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; padding: 20px; }
        
        /* Receipt */
        .receipt-page { width: 300px; margin: 0 auto; font-family: monospace; font-size: 12px; color: #000; padding: 20px; border: 1px dashed #ccc; background: #fff; }
        .receipt-header { text-align: center; margin-bottom: 20px; }
        .receipt-title { font-weight: bold; font-size: 16px; text-transform: uppercase; margin-bottom: 5px; }
        .receipt-divider { border-top: 1px dashed #000; margin: 10px 0; }
        .receipt-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .receipt-table th, .receipt-table td { padding: 2px 0; vertical-align: top; }
        .receipt-table th { border-bottom: 1px dashed #000; text-align: left; }
        .receipt-totals { width: 100%; margin-top: 10px; }
        .receipt-totals td { padding: 2px 0; }
    </style>
</head>
<body>
    @include('pdf.partials.order-receipt')
</body>
</html>
