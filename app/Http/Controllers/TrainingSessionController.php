<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Models\Client;
use App\Models\TrainingSession;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TrainingSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TrainingSession::class);

        $trainer = $request->user();

        $month = $request->date('month') ?? CarbonImmutable::now();
        $rangeStart = $month->startOfMonth()->subDays(7);
        $rangeEnd = $month->endOfMonth()->addDays(7);

        $sessions = $trainer->trainingSessions()
            ->with('client:id,name')
            ->whereBetween('scheduled_at', [$rangeStart, $rangeEnd])
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (TrainingSession $session): array => $this->present($session));

        return Inertia::render('sessions/index', [
            'sessions' => $sessions,
            'month' => $month->format('Y-m-d'),
            'clients' => $trainer->clients()->orderBy('name')->get(['id', 'name', 'default_rate']),
            'currency' => $trainer->trainerProfile()->value('currency'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TrainingSession::class);

        $data = $this->validated($request);
        $this->assertClientBelongsToTrainer($request, (int) $data['client_id']);

        $request->user()->trainingSessions()->create($data);

        return back()->with('status', 'Session logged.');
    }

    public function update(Request $request, TrainingSession $session): RedirectResponse
    {
        $this->authorize('update', $session);

        $data = $this->validated($request);
        $this->assertClientBelongsToTrainer($request, (int) $data['client_id']);

        $session->update($data);

        return back()->with('status', 'Session updated.');
    }

    public function destroy(TrainingSession $session): RedirectResponse
    {
        $this->authorize('delete', $session);

        $session->delete();

        return back()->with('status', 'Session deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(TrainingSession $session): array
    {
        return [
            'id' => $session->id,
            'client_id' => $session->client_id,
            'client_name' => $session->client->name,
            'scheduled_at' => $session->scheduled_at->toIso8601String(),
            'duration_minutes' => $session->duration_minutes,
            'rate' => $session->rate,
            'status' => $session->status->value,
            'status_label' => $session->status->label(),
            'notes' => $session->notes,
            'invoiced' => $session->invoice_id !== null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'client_id' => ['required', 'integer'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:600'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'status' => ['required', Rule::enum(SessionStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function assertClientBelongsToTrainer(Request $request, int $clientId): void
    {
        abort_unless(
            Client::query()->forTrainer($request->user())->whereKey($clientId)->exists(),
            422,
            'That client does not belong to you.',
        );
    }
}
