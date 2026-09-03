<?php

namespace App\Http\Controllers;

use App\Models\RecurringInvoice;
use App\Services\RecurringInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RecurringInvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', RecurringInvoice::class);

        $schedules = $request->user()->recurringInvoices()
            ->with('client:id,name')
            ->latest()
            ->get()
            ->map(fn (RecurringInvoice $schedule): array => [
                'id' => $schedule->id,
                'client_name' => $schedule->client->name,
                'interval' => $schedule->interval,
                'amount' => $this->templateTotal($schedule),
                'auto_send' => $schedule->auto_send,
                'next_run_at' => $schedule->next_run_at->toDateString(),
                'last_generated_at' => $schedule->last_generated_at?->toDateString(),
                'status' => $schedule->status,
            ]);

        return Inertia::render('recurring/index', [
            'schedules' => $schedules,
            'clients' => $request->user()->clients()->orderBy('name')->get(['id', 'name', 'default_rate']),
            'currency' => $request->user()->trainerProfile()->value('currency'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', RecurringInvoice::class);

        $data = $this->validated($request);

        $request->user()->recurringInvoices()->create([
            'client_id' => $request->user()->clients()->whereKey($data['client_id'])->firstOrFail()->id,
            'template' => [
                'items' => $data['items'],
                'discount_total' => 0,
                'tax_rate' => $data['tax_rate'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'allowed_methods' => $data['allowed_methods'] ?? ['upi_manual'],
            ],
            'interval' => $data['interval'],
            'due_days' => $data['due_days'],
            'auto_send' => $data['auto_send'] ?? true,
            'next_run_at' => $data['next_run_at'],
            'status' => 'active',
        ]);

        return back()->with('status', 'Recurring schedule created.');
    }

    public function update(Request $request, RecurringInvoice $recurring): RedirectResponse
    {
        $this->authorize('update', $recurring);

        $recurring->update($request->validate([
            'status' => ['sometimes', Rule::in(['active', 'paused'])],
            'next_run_at' => ['sometimes', 'date'],
            'auto_send' => ['sometimes', 'boolean'],
        ]));

        return back()->with('status', 'Schedule updated.');
    }

    public function destroy(RecurringInvoice $recurring): RedirectResponse
    {
        $this->authorize('delete', $recurring);

        $recurring->delete();

        return back()->with('status', 'Schedule removed.');
    }

    public function runNow(Request $request, RecurringInvoice $recurring, RecurringInvoiceService $service): RedirectResponse
    {
        $this->authorize('update', $recurring);

        $invoice = $service->generate($recurring);

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice generated.');
    }

    private function templateTotal(RecurringInvoice $schedule): float
    {
        $total = 0.0;
        $items = $schedule->template['items'] ?? [];

        foreach (is_array($items) ? $items : [] as $item) {
            $total += (float) ($item['quantity'] ?? 1) * (float) ($item['unit_amount'] ?? 0);
        }

        return round($total, 2);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'client_id' => ['required', 'integer'],
            'interval' => ['required', Rule::in(['week', 'month', 'quarter', 'year'])],
            'due_days' => ['required', 'integer', 'min:0', 'max:120'],
            'next_run_at' => ['required', 'date'],
            'auto_send' => ['boolean'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'allowed_methods' => ['array'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_amount' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
