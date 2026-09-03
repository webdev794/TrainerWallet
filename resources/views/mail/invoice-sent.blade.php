@component('mail::message')
# Invoice {{ $invoice->number }}

Hi {{ $invoice->client->name }},

{{ $invoice->trainer->trainerProfile->business_name ?? $invoice->trainer->name }} has sent you an invoice.

**Amount due:** {{ number_format($invoice->outstanding(), 2) }} {{ $invoice->currency }}
@if($invoice->due_date)
**Due:** {{ $invoice->due_date->format('d M Y') }}
@endif

@component('mail::button', ['url' => $payUrl])
View & pay invoice
@endcomponent

You can pay by UPI or card. A PDF copy is attached.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
