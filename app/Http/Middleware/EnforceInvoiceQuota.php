<?php

namespace App\Http\Middleware;

use App\Support\Feature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceInvoiceQuota
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $profile = $request->user()?->trainerProfile;

        if ($profile !== null && ! Feature::for($profile)->canCreateInvoice()) {
            return redirect()->route('billing.show')->with(
                'error',
                'You have reached the Free plan limit of '.Feature::for($profile)->invoiceLimit().' invoices this month. Upgrade to Pro for unlimited invoicing.',
            );
        }

        return $next($request);
    }
}
