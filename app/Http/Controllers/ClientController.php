<?php

namespace App\Http\Controllers;

use App\Enums\ClientStatus;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $trainer = $request->user();
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();

        $validStatuses = array_column(ClientStatus::cases(), 'value');

        $clients = Client::query()
            ->forTrainer($trainer)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(
                in_array($status, $validStatuses, true),
                fn ($query) => $query->where('status', $status),
            )
            ->withCount('trainingSessions')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Client $client): array => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'default_rate' => $client->default_rate,
                'payment_preference' => $client->payment_preference,
                'notes' => $client->notes,
                'status' => $client->status->value,
                'invited' => $client->isInvited(),
                'sessions_count' => $client->training_sessions_count,
            ]);

        return Inertia::render('clients/index', [
            'clients' => $clients,
            'filters' => ['search' => $search, 'status' => $status],
            'currency' => $trainer->trainerProfile()->value('currency'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Client::class);

        $data = $this->validated($request);

        $request->user()->clients()->create($data);

        return back()->with('status', 'Client added.');
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->update($this->validated($request));

        return back()->with('status', 'Client updated.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        $client->delete();

        return back()->with('status', 'Client removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'default_rate' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'payment_preference' => ['nullable', Rule::in(['upi', 'card', 'cash'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(ClientStatus::class)],
        ]);
    }
}
