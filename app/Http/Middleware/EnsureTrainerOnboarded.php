<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrainerOnboarded
{
    /**
     * Redirect trainers to the onboarding wizard until they have completed it.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->isTrainer()) {
            $profile = $user->trainerProfile;

            if ($profile === null || ! $profile->hasOnboarded()) {
                return redirect()->route('onboarding.show');
            }
        }

        return $next($request);
    }
}
