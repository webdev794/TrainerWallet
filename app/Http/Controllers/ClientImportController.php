<?php

namespace App\Http\Controllers;

use App\Enums\ClientStatus;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientImportController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $this->authorize('create', Client::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:1024'],
        ]);

        $trainer = $request->user();
        $handle = fopen($request->file('file')->getRealPath(), 'r');

        if ($handle === false) {
            return back()->with('error', 'Could not read that file.');
        }

        $header = null;
        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map(fn ($value): string => strtolower(trim((string) $value)), $row);

                continue;
            }

            $record = array_combine($header, array_pad($row, count($header), null));
            $name = trim((string) ($record['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $trainer->clients()->create([
                'name' => $name,
                'email' => ($record['email'] ?? null) ?: null,
                'phone' => ($record['phone'] ?? null) ?: null,
                'default_rate' => is_numeric($record['rate'] ?? null) ? $record['rate'] : null,
                'status' => ClientStatus::Active,
            ]);

            $imported++;
        }

        fclose($handle);

        return back()->with('status', "Imported {$imported} client(s).");
    }
}
