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
        $clientIds = $request->user()->clientRecordIds();

        $sessions = $clientIds === [] ? collect() : TrainingSession::query()
            ->whereIn('client_id', $clientIds)
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
                'invoiced' => $session->invoice_id !== null,
            ]);

        return Inertia::render('portal/sessions', [
            'sessions' => $sessions,
        ]);
    }
}
