<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function index(Request $request): Response
    {
        $trainer = $request->user();
        $now = CarbonImmutable::now();
        $start = $now->startOfMonth()->subMonths(5);

        $rows = $trainer->payments()
            ->where('status', PaymentStatus::Succeeded->value)
            ->where('paid_at', '>=', $start)
            ->get(['amount', 'net_amount', 'paid_at']);

        $series = collect(range(0, 5))->map(function (int $i) use ($start, $rows): array {
            $month = $start->addMonths($i);
            $inMonth = $rows->filter(fn ($p) => $p->paid_at !== null
                && $p->paid_at->year === $month->year
                && $p->paid_at->month === $month->month);

            return [
                'month' => $month->format('M y'),
                'gross' => round((float) $inMonth->sum('amount'), 2),
                'net' => round((float) $inMonth->sum('net_amount'), 2),
            ];
        })->all();

        $byClient = DB::table('invoices')
            ->join('clients', 'clients.id', '=', 'invoices.client_id')
            ->where('invoices.trainer_id', $trainer->id)
            ->whereIn('invoices.status', [
                InvoiceStatus::Sent->value,
                InvoiceStatus::Viewed->value,
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::Overdue->value,
            ])
            ->groupBy('clients.id', 'clients.name')
            ->orderByDesc('balance')
            ->limit(10)
            ->get([
                'clients.name as client_name',
                DB::raw('COUNT(*) as open_invoices'),
                DB::raw('SUM(invoices.total - invoices.amount_paid) as balance'),
            ])
            ->map(fn (object $row): array => [
                'client_name' => (string) $row->client_name,
                'open_invoices' => (int) $row->open_invoices,
                'balance' => round((float) $row->balance, 2),
            ]);

        return Inertia::render('reports/index', [
            'series' => $series,
            'byClient' => $byClient,
            'totals' => [
                'collected_ytd' => round((float) $trainer->payments()
                    ->where('status', PaymentStatus::Succeeded->value)
                    ->where('paid_at', '>=', $now->startOfYear())
                    ->sum('amount'), 2),
                'net_ytd' => round((float) $trainer->payments()
                    ->where('status', PaymentStatus::Succeeded->value)
                    ->where('paid_at', '>=', $now->startOfYear())
                    ->sum('net_amount'), 2),
            ],
            'currency' => $trainer->trainerProfile()->value('currency') ?? config('coachpay.default_currency'),
        ]);
    }

    public function exportPayments(Request $request): StreamedResponse
    {
        $trainer = $request->user();

        $filename = 'payments-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($trainer): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fputcsv($out, ['Date', 'Invoice', 'Client', 'Method', 'Amount', 'Fee', 'Net', 'Currency', 'Status', 'Reference']);

            $trainer->payments()
                ->with('invoice.client')
                ->orderBy('created_at')
                ->chunk(200, function ($payments) use ($out): void {
                    foreach ($payments as $payment) {
                        fputcsv($out, [
                            optional($payment->paid_at ?? $payment->created_at)->toDateString(),
                            $payment->invoice->number,
                            $payment->invoice->client->name,
                            $payment->gateway->label(),
                            $payment->amount,
                            $payment->fee_amount,
                            $payment->net_amount,
                            $payment->currency,
                            $payment->status->value,
                            $payment->reference,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
