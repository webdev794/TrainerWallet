<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform fee
    |--------------------------------------------------------------------------
    | Percentage the platform retains from each processed payment before the
    | remainder is settled to the trainer's connected account.
    */

    'platform_fee_percent' => (float) env('PLATFORM_FEE_PERCENT', 0),

    /*
    |--------------------------------------------------------------------------
    | Default currency
    |--------------------------------------------------------------------------
    */

    'default_currency' => env('COACHPAY_DEFAULT_CURRENCY', 'INR'),

    /*
    |--------------------------------------------------------------------------
    | Plans
    |--------------------------------------------------------------------------
    | Feature limits per subscription tier. `null` means unlimited.
    */

    'plans' => [
        'free' => [
            'price' => 0,
            'invoice_limit_per_month' => 5,
            'branded_pdf' => false,
            'recurring_invoices' => false,
            'advanced_reports' => false,
        ],
        'pro' => [
            'price' => 19,
            'invoice_limit_per_month' => null,
            'branded_pdf' => true,
            'recurring_invoices' => true,
            'advanced_reports' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reminder schedule
    |--------------------------------------------------------------------------
    | Offsets in days relative to an invoice due date. Negative = before due,
    | positive = after due (dunning).
    */

    'reminder_offsets' => [
        'pre_due' => [-3, -1],
        'overdue' => [1, 3, 7],
    ],

];
