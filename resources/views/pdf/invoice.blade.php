<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1e293b; font-size: 12px; margin: 0; padding: 32px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .muted { color: #64748b; }
        .row { width: 100%; }
        .row:after { content: ""; display: table; clear: both; }
        .col { float: left; width: 50%; }
        .right { text-align: right; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 24px; }
        table.items th { text-align: left; border-bottom: 2px solid #e2e8f0; padding: 8px 6px; font-size: 11px; text-transform: uppercase; color: #64748b; }
        table.items td { padding: 8px 6px; border-bottom: 1px solid #e2e8f0; }
        .totals { margin-top: 16px; width: 40%; float: right; }
        .totals td { padding: 4px 6px; }
        .totals .grand { font-weight: bold; font-size: 14px; border-top: 2px solid #e2e8f0; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; background: #ecfdf5; color: #047857; font-size: 11px; }
        .qr { margin-top: 32px; }
        .qr img { width: 130px; height: 130px; }
    </style>
</head>
<body>
    <div class="row">
        <div class="col">
            <h1>{{ $profile->business_name ?? $invoice->trainer->name }}</h1>
            @if($profile && $profile->address)<div class="muted">{{ $profile->address }}</div>@endif
            @if($profile && $profile->tax_id)<div class="muted">Tax ID: {{ $profile->tax_id }}</div>@endif
        </div>
        <div class="col right">
            <h1>INVOICE</h1>
            <div class="muted">{{ $invoice->number }}</div>
            <div><span class="badge">{{ $invoice->status->label() }}</span></div>
        </div>
    </div>

    <div class="row" style="margin-top: 24px;">
        <div class="col">
            <div class="muted">Bill to</div>
            <strong>{{ $invoice->client->name }}</strong><br>
            <span class="muted">{{ $invoice->client->email }}</span>
        </div>
        <div class="col right">
            @if($invoice->issued_at)<div><span class="muted">Issued:</span> {{ $invoice->issued_at->format('d M Y') }}</div>@endif
            @if($invoice->due_date)<div><span class="muted">Due:</span> {{ $invoice->due_date->format('d M Y') }}</div>@endif
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Qty</th>
                <th class="right">Unit</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                    <td class="right">{{ number_format((float) $item->unit_amount, 2) }}</td>
                    <td class="right">{{ number_format((float) $item->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="muted">Subtotal</td><td class="right">{{ number_format((float) $invoice->subtotal, 2) }}</td></tr>
        @if((float) $invoice->discount_total > 0)
            <tr><td class="muted">Discount</td><td class="right">-{{ number_format((float) $invoice->discount_total, 2) }}</td></tr>
        @endif
        @if((float) $invoice->tax_total > 0)
            <tr><td class="muted">Tax ({{ rtrim(rtrim(number_format((float) $invoice->tax_rate, 2), '0'), '.') }}%)</td><td class="right">{{ number_format((float) $invoice->tax_total, 2) }}</td></tr>
        @endif
        <tr class="grand"><td>Total</td><td class="right">{{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }}</td></tr>
        @if((float) $invoice->amount_paid > 0)
            <tr><td class="muted">Paid</td><td class="right">-{{ number_format((float) $invoice->amount_paid, 2) }}</td></tr>
            <tr class="grand"><td>Balance</td><td class="right">{{ number_format($invoice->outstanding(), 2) }} {{ $invoice->currency }}</td></tr>
        @endif
    </table>

    <div style="clear: both;"></div>

    @if($upiQr)
        <div class="qr">
            <div class="muted">Scan to pay by UPI</div>
            <img src="{{ $upiQr }}" alt="UPI QR">
        </div>
    @endif

    @if($invoice->notes)
        <div style="margin-top: 24px;" class="muted">{{ $invoice->notes }}</div>
    @endif
</body>
</html>
