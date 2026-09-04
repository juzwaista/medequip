<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order {{ $po->po_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; color: #333; line-height: 1.4; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .title { font-size: 24px; font-weight: bold; margin: 0; }
        .po-number { color: #666; font-size: 16px; margin: 5px 0 0; }
        .info-grid { display: table; width: 100%; margin-bottom: 30px; }
        .info-col { display: table-cell; width: 50%; }
        .info-title { font-size: 12px; font-weight: bold; color: #666; text-transform: uppercase; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f3f4f6; text-align: left; padding: 10px; font-size: 12px; color: #374151; border-bottom: 1px solid #d1d5db; }
        td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; font-size: 16px; background: #f9fafb; border-top: 2px solid #d1d5db; }
        .notes-section { padding: 15px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1 class="title">PURCHASE ORDER</h1>
            <p class="po-number">{{ $po->po_number }}</p>
        </div>
        <div style="text-align: right;">
            <p style="margin:0; font-weight:bold;">{{ $distributor->company_name ?? $distributor->user->name }}</p>
            <p style="margin:0; color:#666;">Date: {{ \Carbon\Carbon::parse($po->created_at)->format('M d, Y') }}</p>
            <p style="margin:0; color:#666;">Expected: {{ $po->expected_delivery_date ? \Carbon\Carbon::parse($po->expected_delivery_date)->format('M d, Y') : 'TBD' }}</p>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-col">
            <div class="info-title">Vendor / Supplier</div>
            <p style="margin:0; font-weight:bold;">{{ $po->supplier->name }}</p>
            <p style="margin:0;">Attn: {{ $po->supplier->contact_person ?? 'Sales Team' }}</p>
            <p style="margin:0;">{{ $po->supplier->email }}</p>
            @if($po->supplier->address)
            <p style="margin:0;">{{ $po->supplier->address }}</p>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item / Description</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($po->items as $item)
            <tr>
                <td>{{ $item->product->name }}</td>
                <td class="text-right">{{ $item->quantity_ordered }}</td>
                <td class="text-right">₱{{ number_format($item->unit_cost, 2) }}</td>
                <td class="text-right">₱{{ number_format($item->quantity_ordered * $item->unit_cost, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="text-right">GRAND TOTAL</td>
                <td class="text-right">₱{{ number_format($po->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @if($po->notes)
    <div class="notes-section">
        <div class="info-title">Notes / Instructions</div>
        <p style="margin:0; white-space:pre-wrap;">{{ $po->notes }}</p>
    </div>
    @endif
</body>
</html>
