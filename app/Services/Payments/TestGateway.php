<?php

namespace App\Services\Payments;

use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;

/**
 * Sandbox gateway: no real charge. Checkout redirects straight back to the
 * callback marked as paid, so the entire subscribe flow can be tested before
 * live keys exist. Selected when PAYMENT_GATEWAY=test (the default).
 */
class TestGateway implements PaymentGateway
{
    public function checkout(SubscriptionPayment $payment, string $callbackUrl): string
    {
        $payment->update(['reference' => 'test_' . $payment->id]);

        // Simulate the gateway approving and returning the user.
        return $callbackUrl . (str_contains($callbackUrl, '?') ? '&' : '?') . 'result=paid';
    }

    public function verify(Request $request, SubscriptionPayment $payment): bool
    {
        return $request->query('result') === 'paid' || $request->input('result') === 'paid';
    }

    public function webhookReference(Request $request): ?string
    {
        return $request->input('reference');
    }

    public function name(): string
    {
        return 'test';
    }
}
