@component('mail::message')
# Payment received

Hi {{ $invoice->client->name }},

We've received your payment for invoice **{{ $invoice->number }}**.

**Amount paid:** {{ number_format((float) $invoice->amount_paid, 2) }} {{ $invoice->currency }}
**Paid on:** {{ optional($invoice->paid_at)->format('d M Y') }}

Thanks for your business!<br>
{{ $invoice->trainer->trainerProfile->business_name ?? $invoice->trainer->name }}
@endcomponent
