<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use App\Notifications\ClientInvitationNotification;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClientInviteController extends Controller
{
    public function __invoke(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        if ($client->email === null) {
            throw ValidationException::withMessages([
                'email' => 'Add an email address for this client before inviting them.',
            ]);
        }

        if ($client->isInvited()) {
            return back()->with('status', 'This client already has a portal account.');
        }

        $existing = User::query()->where('email', $client->email)->first();

        if ($existing !== null) {
            if ($existing->isClient()) {
                // They already self-registered — just connect the records.
                $client->update(['client_user_id' => $existing->id]);

                return back()->with('status', 'This client already has an account — linked to your records.');
            }

            throw ValidationException::withMessages([
                'email' => 'An account already exists for that email address.',
            ]);
        }

        $user = DB::transaction(function () use ($client): User {
            $user = User::create([
                'name' => $client->name,
                'email' => $client->email,
                'password' => Str::password(32),
                'role' => UserRole::Client,
            ]);

            $user->forceFill(['email_verified_at' => CarbonImmutable::now()])->save();

            $client->update(['client_user_id' => $user->id]);

            return $user;
        });

        $token = Password::broker()->createToken($user);
        $businessName = $request->user()->trainerProfile()->value('business_name') ?? $request->user()->name;
        $user->notify(new ClientInvitationNotification($token, $businessName));

        return back()->with('status', "Invitation sent to {$client->email}.");
    }
}
