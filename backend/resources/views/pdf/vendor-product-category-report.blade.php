<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vendor Product Categories Report</title>
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
        .data-table th, .data-table td { padding: 10px 10px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: middle; }
        .data-table th { background-color: #3f0909; color: #f8fafc; font-weight: 700; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #bd2427; }
        .data-table tbody tr:nth-child(even) { background-color: #f8fafc; }
        .data-table td { font-size: 11px; color: #1e293b; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 9px; font-weight: 800; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
        
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
                <h3 class="report-title">Product Categories Report</h3>
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

        <div class="section-title">Store Categories</div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Category Details</th>
                    <th style="width: 150px; text-align: center;">Inventory Count</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                    <tr>
                        <td>
                            <div style="font-weight: bold; font-size: 12px; color: #0f172a;">{{ $category->category_name }}</div>
                            @if($category->description)
                                <div style="font-size: 9px; color: #64748b; margin-top: 4px;">{{ $category->description }}</div>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <span class="badge">{{ $category->products_count }} Items</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>
</body>
</html>
