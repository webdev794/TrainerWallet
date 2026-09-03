<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentGatewayType;
use App\Enums\SessionStatus;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Invoice;
use App\Models\TrainingSession;
use App\Services\InvoiceDocumentService;
use App\Services\InvoiceNumberService;
use App\Services\InvoiceReminderService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceNumberService $numbers,
        private readonly InvoiceDocumentService $documents,
        private readonly InvoiceReminderService $reminders,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $status = $request->string('status')->toString();

        $invoices = $request->user()->invoices()
            ->with('client:id,name')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'client_name' => $invoice->client->name,
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
                'total' => $invoice->total,
                'outstanding' => $invoice->outstanding(),
                'currency' => $invoice->currency,
                'due_date' => $invoice->due_date?->toDateString(),
                'issued_at' => $invoice->issued_at?->toDateString(),
            ]);

        return Inertia::render('invoices/index', [
            'invoices' => $invoices,
            'filters' => ['status' => $status],
            'currency' => $request->user()->trainerProfile()->value('currency'),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Invoice::class);

        return Inertia::render('invoices/create', $this->builderProps($request));
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $invoice = DB::transaction(function () use ($request): Invoice {
            $trainer = $request->user();
            $data = $request->validated();

            $client = $trainer->clients()->whereKey($data['client_id'])->firstOrFail();

            $invoice = $trainer->invoices()->create([
                'client_id' => $client->id,
                'number' => $this->numbers->next($trainer->trainerProfile),
                'status' => InvoiceStatus::Draft,
                'currency' => $trainer->trainerProfile->currency,
                'discount_total' => $data['discount_total'] ?? 0,
                'tax_rate' => $data['tax_rate'] ?? 0,
                'due_date' => $data['due_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'allowed_methods' => $data['allowed_methods'] ?? [PaymentGatewayType::UpiManual->value],
            ]);

            $this->syncItems($invoice, $data['items']);

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)->with('status', 'Draft invoice created.');
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['items', 'client', 'payments' => fn ($query) => $query->latest()]);

        return Inertia::render('invoices/show', [
            'invoice' => $this->present($invoice),
            'publicUrl' => route('public-invoice.show', $invoice->public_token),
        ]);
    }

    public function edit(Request $request, Invoice $invoice): Response
    {
        $this->authorize('update', $invoice);

        $invoice->load('items');

        return Inertia::render('invoices/create', [
            ...$this->builderProps($request),
            'invoice' => $this->present($invoice),
        ]);
    }

    public function update(StoreInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        DB::transaction(function () use ($request, $invoice): void {
            $data = $request->validated();

            $invoice->update([
                'client_id' => $request->user()->clients()->whereKey($data['client_id'])->firstOrFail()->id,
                'discount_total' => $data['discount_total'] ?? 0,
                'tax_rate' => $data['tax_rate'] ?? 0,
                'due_date' => $data['due_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'allowed_methods' => $data['allowed_methods'] ?? [PaymentGatewayType::UpiManual->value],
            ]);

            $invoice->items()->delete();
            $invoice->trainingSessions()->update(['invoice_id' => null]);
            $this->syncItems($invoice->fresh(), $data['items']);
        });

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice updated.');
    }

    public function send(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('send', $invoice);

        if (! in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Sent], true)) {
            return back()->with('error', 'This invoice can no longer be sent.');
        }

        $invoice->update([
            'status' => InvoiceStatus::Sent,
            'issued_at' => $invoice->issued_at ?? CarbonImmutable::now(),
        ]);

        $this->reminders->schedule($invoice->fresh());
        $this->documents->emailInvoiceToClient($invoice->fresh());

        return back()->with('status', $invoice->client->email
            ? "Invoice emailed to {$invoice->client->email}."
            : 'Invoice marked as sent. Add a client email to deliver it automatically.');
    }

    public function void(Invoice $invoice): RedirectResponse
    {
        $this->authorize('send', $invoice);

        abort_if($invoice->status === InvoiceStatus::Paid, 422, 'Paid invoices cannot be voided.');

        $invoice->update(['status' => InvoiceStatus::Void]);
        $invoice->trainingSessions()->update(['invoice_id' => null]);

        return back()->with('status', 'Invoice voided.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        $invoice->trainingSessions()->update(['invoice_id' => null]);
        $invoice->delete();

        return redirect()->route('invoices.index')->with('status', 'Draft deleted.');
    }

    public function pdf(Invoice $invoice): StreamedResponse
    {
        $this->authorize('view', $invoice);

        $path = $invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)
            ? $invoice->pdf_path
            : $this->documents->generateInvoice($invoice);

        return Storage::disk('local')->download($path, "Invoice-{$invoice->number}.pdf");
    }

    /**
     * @return array<string, mixed>
     */
    private function builderProps(Request $request): array
    {
        $trainer = $request->user();

        $clients = $trainer->clients()->orderBy('name')->get(['id', 'name', 'email', 'default_rate']);
        $packages = $trainer->packages()->where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'amount', 'type']);

        $unbilledSessions = TrainingSession::query()
            ->where('trainer_id', $trainer->id)
            ->where('status', SessionStatus::Completed->value)
            ->whereNull('invoice_id')
            ->with('client:id,name')
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (TrainingSession $session): array => [
                'id' => $session->id,
                'client_id' => $session->client_id,
                'client_name' => $session->client->name,
                'scheduled_at' => $session->scheduled_at->toIso8601String(),
                'rate' => $session->rate,
            ]);

        return [
            'clients' => $clients,
            'packages' => $packages,
            'unbilledSessions' => $unbilledSessions,
            'currency' => $trainer->trainerProfile->currency,
            'allowedMethodOptions' => collect(PaymentGatewayType::cases())
                ->map(fn (PaymentGatewayType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])->values(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(Invoice $invoice, array $items): void
    {
        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $unit = (float) $item['unit_amount'];

            $created = $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_amount' => $unit,
                'amount' => round($quantity * $unit, 2),
                'training_session_id' => $item['training_session_id'] ?? null,
            ]);

            if (! empty($item['training_session_id'])) {
                TrainingSession::query()
                    ->where('trainer_id', $invoice->trainer_id)
                    ->whereKey($item['training_session_id'])
                    ->update(['invoice_id' => $invoice->id]);

                $created->forceFill(['training_session_id' => $item['training_session_id']])->save();
            }
        }

        $invoice->load('items');
        $invoice->recalculateTotals();
        $invoice->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'status' => $invoice->status->value,
            'status_label' => $invoice->status->label(),
            'editable' => $invoice->isEditable(),
            'client_id' => $invoice->client_id,
            'client' => [
                'id' => $invoice->client->id,
                'name' => $invoice->client->name,
                'email' => $invoice->client->email,
            ],
            'currency' => $invoice->currency,
            'subtotal' => $invoice->subtotal,
            'discount_total' => $invoice->discount_total,
            'tax_rate' => $invoice->tax_rate,
            'tax_total' => $invoice->tax_total,
            'total' => $invoice->total,
            'amount_paid' => $invoice->amount_paid,
            'outstanding' => $invoice->outstanding(),
            'due_date' => $invoice->due_date?->toDateString(),
            'issued_at' => $invoice->issued_at?->toIso8601String(),
            'paid_at' => $invoice->paid_at?->toIso8601String(),
            'notes' => $invoice->notes,
            'allowed_methods' => $invoice->allowed_methods ?? [],
            'items' => $invoice->items->map(fn ($item): array => [
                'id' => $item->id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_amount' => $item->unit_amount,
                'amount' => $item->amount,
                'training_session_id' => $item->training_session_id,
            ])->values(),
            'payments' => $invoice->payments->map(fn ($payment): array => [
                'id' => $payment->id,
                'gateway' => $payment->gateway->value,
                'gateway_label' => $payment->gateway->label(),
                'amount' => $payment->amount,
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
                'reference' => $payment->reference,
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'created_at' => $payment->created_at?->toIso8601String(),
            ])->values(),
        ];
    }
}
