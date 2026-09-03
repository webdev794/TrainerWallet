<?php

namespace App\Http\Controllers\Payments;

use App\Enums\PaymentGatewayType;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PublicPaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function start(Request $request, string $token, string $gateway): RedirectResponse
    {
        $invoice = Invoice::query()->where('public_token', $token)->firstOrFail();
        $type = PaymentGatewayType::tryFrom($gateway);

        abort_if($type === null || ! $type->isOnline(), 404);

        try {
            $checkout = $this->payments->beginCheckout(
                $invoice,
                $type,
                fn (Payment $payment): array => [
                    route('public-invoice.return', ['token' => $token, 'payment' => $payment->id]),
                    route('public-invoice.show', $token).'?status=cancelled',
                ],
            );
        } catch (RuntimeException $e) {
            return redirect()->route('public-invoice.show', $token)->with('error', $e->getMessage());
        }

        return redirect()->away($checkout->redirectUrl);
    }

    public function return(Request $request, string $token, Payment $payment): RedirectResponse
    {
        abort_unless($payment->invoice->public_token === $token, 404);

        try {
            $this->payments->completeReturn($payment);
        } catch (RuntimeException $e) {
            return redirect()->route('public-invoice.show', $token)->with('error', $e->getMessage());
        }

        $paid = $payment->fresh()->status->value === 'succeeded';

        return redirect()->route('public-invoice.show', $token)
            ->with($paid ? 'status' : 'error', $paid
                ? 'Payment received. Thank you!'
                : 'Payment was not completed.');
    }

    public function submitUpi(Request $request, string $token): RedirectResponse
    {
        $invoice = Invoice::query()->where('public_token', $token)->firstOrFail();

        abort_unless($invoice->isPayable(), 422);
        abort_unless(in_array(PaymentGatewayType::UpiManual->value, $invoice->allowed_methods ?? [PaymentGatewayType::UpiManual->value], true), 422);

        $data = $request->validate([
            'reference' => ['required', 'string', 'max:64'],
        ]);

        $this->payments->recordManualPayment(
            $invoice,
            PaymentGatewayType::UpiManual,
            $invoice->outstanding(),
            $data['reference'],
            confirmed: false,
        );

        return redirect()->route('public-invoice.show', $token)
            ->with('status', 'Thanks! We recorded your UPI reference. The trainer will confirm receipt shortly.');
    }
}
