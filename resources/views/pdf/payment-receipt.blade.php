<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1e293b; font-size: 12px; padding: 32px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .muted { color: #64748b; }
        .tag { display: inline-block; padding: 3px 10px; border-radius: 999px; background: #ecfdf5; color: #047857; font-size: 11px; }
        .box { margin-top: 24px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 6px 4px; }
        .right { text-align: right; }
        .grand { font-weight: bold; font-size: 15px; border-top: 2px solid #e2e8f0; }
    </style>
</head>
<body>
    <div style="width:100%">
        <div style="float:left">
            <h1>{{ $profile->business_name ?? $invoice->trainer->name }}</h1>
            <div class="muted">Payment receipt</div>
        </div>
        <div style="float:right; text-align:right">
            <div class="tag">RECEIVED</div>
            <div class="muted">Receipt #{{ $payment->id }}</div>
        </div>
    </div>
    <div style="clear:both"></div>

    <div class="box">
        <table>
            <tr><td class="muted">Received from</td><td class="right">{{ $invoice->client->name }}</td></tr>
            <tr><td class="muted">For invoice</td><td class="right">{{ $invoice->number }}</td></tr>
            <tr><td class="muted">Payment method</td><td class="right">{{ $payment->gateway->label() }}</td></tr>
            @if($payment->reference)
                <tr><td class="muted">Reference</td><td class="right">{{ $payment->reference }}</td></tr>
            @endif
            <tr><td class="muted">Date</td><td class="right">{{ optional($payment->paid_at ?? $payment->created_at)->format('d M Y') }}</td></tr>
            @if((float) $payment->fee_amount > 0)
                <tr><td class="muted">Processing fee</td><td class="right">{{ number_format((float) $payment->fee_amount, 2) }} {{ $payment->currency }}</td></tr>
            @endif
            <tr class="grand"><td>Amount received</td><td class="right">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</td></tr>
        </table>
    </div>

    <p class="muted" style="margin-top: 20px;">
        Invoice total {{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }} ·
        paid to date {{ number_format((float) $invoice->amount_paid, 2) }} {{ $invoice->currency }} ·
        balance {{ number_format($invoice->outstanding(), 2) }} {{ $invoice->currency }}
    </p>

    <p class="muted" style="margin-top: 24px;">Thank you for your payment.</p>
</body>
</html>
