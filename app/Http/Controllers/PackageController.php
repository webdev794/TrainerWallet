<?php

namespace App\Http\Controllers;

use App\Enums\PackageType;
use App\Models\Package;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PackageController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Package::class);

        $packages = $request->user()->packages()
            ->orderBy('name')
            ->get()
            ->map(fn (Package $package): array => [
                'id' => $package->id,
                'name' => $package->name,
                'description' => $package->description,
                'type' => $package->type->value,
                'type_label' => $package->type->label(),
                'amount' => $package->amount,
                'sessions_count' => $package->sessions_count,
                'billing_interval' => $package->billing_interval,
                'duration_minutes' => $package->duration_minutes,
                'is_active' => $package->is_active,
                'is_bookable' => $package->is_bookable,
            ]);

        return Inertia::render('packages/index', [
            'packages' => $packages,
            'currency' => $request->user()->trainerProfile()->value('currency'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Package::class);

        $request->user()->packages()->create($this->validated($request));

        return back()->with('status', 'Package created.');
    }

    public function update(Request $request, Package $package): RedirectResponse
    {
        $this->authorize('update', $package);

        $package->update($this->validated($request));

        return back()->with('status', 'Package updated.');
    }

    public function destroy(Package $package): RedirectResponse
    {
        $this->authorize('delete', $package);

        $package->delete();

        return back()->with('status', 'Package deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::enum(PackageType::class)],
            'amount' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'sessions_count' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'billing_interval' => ['nullable', Rule::in(['week', 'month', 'quarter', 'year'])],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:600'],
            'is_active' => ['boolean'],
            'is_bookable' => ['boolean'],
        ]);
    }
}
