<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $trainer = $request->user();
        $now = CarbonImmutable::now();

        $collected = (float) $trainer->payments()
            ->where('status', PaymentStatus::Succeeded->value)
            ->where('paid_at', '>=', $now->startOfMonth())
            ->sum('amount');

        $outstanding = (float) $trainer->invoices()
            ->whereIn('status', [
                InvoiceStatus::Sent->value,
                InvoiceStatus::Viewed->value,
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::Overdue->value,
            ])
            ->selectRaw('COALESCE(SUM(total - amount_paid), 0) as balance')
            ->value('balance');

        $overdueCount = $trainer->invoices()->where('status', InvoiceStatus::Overdue->value)->count();

        $sessionsThisWeek = $trainer->trainingSessions()
            ->whereBetween('scheduled_at', [$now->startOfWeek(), $now->endOfWeek()])
            ->count();

        $recentInvoices = $trainer->invoices()
            ->with('client:id,name')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($invoice): array => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'client_name' => $invoice->client->name,
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
                'total' => $invoice->total,
                'currency' => $invoice->currency,
            ]);

        return Inertia::render('dashboard', [
            'stats' => [
                'collected_this_month' => round($collected, 2),
                'outstanding' => round((float) $outstanding, 2),
                'overdue_count' => $overdueCount,
                'sessions_this_week' => $sessionsThisWeek,
            ],
            'recentInvoices' => $recentInvoices,
            'currency' => $trainer->trainerProfile()->value('currency') ?? config('coachpay.default_currency'),
        ]);
    }
}
