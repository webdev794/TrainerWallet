<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Client;
use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $role = UserRole::from($request->string('role')->toString());

        $user = DB::transaction(function () use ($request, $role): User {
            $user = User::create([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'password' => $request->string('password')->toString(),
                'role' => $role,
            ]);

            if ($role === UserRole::Trainer) {
                $user->trainerProfile()->create([
                    'business_name' => $user->name,
                    'currency' => config('coachpay.default_currency'),
                    'invoice_prefix' => TrainerProfile::defaultPrefixFor($user->name),
                ]);
            } else {
                // Attach any client records a trainer already created for this email.
                Client::query()
                    ->whereNull('client_user_id')
                    ->where('email', $user->email)
                    ->update(['client_user_id' => $user->id]);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route($role === UserRole::Trainer ? 'onboarding.show' : 'portal.index');
    }
}
