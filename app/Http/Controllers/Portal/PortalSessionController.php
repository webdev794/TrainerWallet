<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortalSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $clientRecord = $request->user()->clientRecord;

        $sessions = $clientRecord === null ? collect() : TrainingSession::query()
            ->where('client_id', $clientRecord->id)
            ->with('trainer:id,name')
            ->orderByDesc('scheduled_at')
            ->limit(100)
            ->get()
            ->map(fn (TrainingSession $session): array => [
                'id' => $session->id,
                'scheduled_at' => $session->scheduled_at->toIso8601String(),
                'duration_minutes' => $session->duration_minutes,
                'status' => $session->status->value,
                'status_label' => $session->status->label(),
                'trainer_name' => $session->trainer->name,
            ]);

        return Inertia::render('portal/sessions', [
            'sessions' => $sessions,
        ]);
    }
}
