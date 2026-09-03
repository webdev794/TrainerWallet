@component('mail::message')
# {{ $overdue ? 'Payment overdue' : 'Friendly reminder' }}

Hi {{ $invoice->client->name }},

@if($overdue)
Invoice **{{ $invoice->number }}** was due on {{ $invoice->due_date->format('d M Y') }} and is still unpaid.
@else
Invoice **{{ $invoice->number }}** is due on {{ $invoice->due_date->format('d M Y') }}.
@endif

**Balance:** {{ number_format($invoice->outstanding(), 2) }} {{ $invoice->currency }}

@component('mail::button', ['url' => $payUrl])
Pay now
@endcomponent

Thanks,<br>
{{ $invoice->trainer->trainerProfile->business_name ?? $invoice->trainer->name }}
@endcomponent
