<?php

namespace App\Http\Controllers\Portal;

use App\Enums\InvoiceStatus;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\TrainingSession;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PortalDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $clientIds = $user->clientRecordIds();
        $now = CarbonImmutable::now();

        $completed = TrainingSession::query()
            ->whereIn('client_id', $clientIds ?: [0])
            ->where('status', SessionStatus::Completed->value)
            ->get(['scheduled_at']);

        $start = $now->startOfMonth()->subMonths(5);
        $series = collect(range(0, 5))->map(function (int $i) use ($start, $completed): array {
            $month = $start->addMonths($i);

            return [
                'month' => $month->format('M'),
                'sessions' => $completed->filter(
                    fn ($s) => $s->scheduled_at->year === $month->year
                        && $s->scheduled_at->month === $month->month,
                )->count(),
            ];
        })->all();

        $nextSession = TrainingSession::query()
            ->whereIn('client_id', $clientIds ?: [0])
            ->where('status', SessionStatus::Scheduled->value)
            ->where('scheduled_at', '>=', $now)
            ->with('trainer:id,name')
            ->orderBy('scheduled_at')
            ->first();

        $openInvoices = Invoice::query()
            ->whereIn('client_id', $clientIds ?: [0])
            ->whereIn('status', [
                InvoiceStatus::Sent->value,
                InvoiceStatus::Viewed->value,
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::Overdue->value,
            ])
            ->count();

        return Inertia::render('portal/index', [
            'linked' => $clientIds !== [],
            'stats' => [
                'completed_total' => $completed->count(),
                'completed_this_month' => $completed->filter(
                    fn ($s) => $s->scheduled_at->greaterThanOrEqualTo($now->startOfMonth()),
                )->count(),
                'streak_weeks' => $this->streakWeeks($completed->pluck('scheduled_at'), $now),
                'open_invoices' => $openInvoices,
            ],
            'series' => $series,
            'nextSession' => $nextSession === null ? null : [
                'scheduled_at' => $nextSession->scheduled_at->toIso8601String(),
                'duration_minutes' => $nextSession->duration_minutes,
                'trainer_name' => $nextSession->trainer->name,
            ],
        ]);
    }

    /**
     * Count consecutive weeks (ending this week) that have at least one completed session.
     *
     * @param  Collection<int, CarbonImmutable>  $dates
     */
    private function streakWeeks(Collection $dates, CarbonImmutable $now): int
    {
        $weeks = $dates
            ->map(fn (CarbonImmutable $date): string => $date->startOfWeek()->toDateString())
            ->unique()
            ->flip();

        $streak = 0;
        $cursor = $now->startOfWeek();

        while ($weeks->has($cursor->toDateString())) {
            $streak++;
            $cursor = $cursor->subWeek();
        }

        return $streak;
    }
}
