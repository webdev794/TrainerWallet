<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1e293b; font-size: 12px; padding: 32px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .muted { color: #64748b; }
        .box { margin-top: 24px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 6px 4px; }
        .right { text-align: right; }
        .grand { font-weight: bold; font-size: 15px; border-top: 2px solid #e2e8f0; }
    </style>
</head>
<body>
    <h1>{{ $profile->business_name ?? $invoice->trainer->name }}</h1>
    <div class="muted">Payment receipt</div>

    <div class="box">
        <table>
            <tr><td class="muted">Invoice</td><td class="right">{{ $invoice->number }}</td></tr>
            <tr><td class="muted">Client</td><td class="right">{{ $invoice->client->name }}</td></tr>
            <tr><td class="muted">Paid on</td><td class="right">{{ optional($invoice->paid_at)->format('d M Y') }}</td></tr>
            @foreach($invoice->payments->where('status', \App\Enums\PaymentStatus::Succeeded) as $payment)
                <tr>
                    <td class="muted">{{ $payment->gateway->label() }}{{ $payment->reference ? ' · '.$payment->reference : '' }}</td>
                    <td class="right">{{ number_format((float) $payment->amount, 2) }}</td>
                </tr>
            @endforeach
            <tr class="grand"><td>Total paid</td><td class="right">{{ number_format((float) $invoice->amount_paid, 2) }} {{ $invoice->currency }}</td></tr>
        </table>
    </div>

    <p class="muted" style="margin-top: 24px;">Thank you for your payment.</p>
</body>
</html>
