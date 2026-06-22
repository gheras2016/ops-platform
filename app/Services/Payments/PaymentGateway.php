<?php

namespace App\Services\Payments;

use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;

/**
 * A pluggable payment gateway. Implementations begin a hosted checkout for a
 * pending subscription payment and verify the result when the user returns.
 */
interface PaymentGateway
{
    /**
     * Begin checkout for the pending payment. Returns the URL to redirect the
     * user to (the gateway's hosted payment page). May store a gateway
     * reference on the payment.
     */
    public function checkout(SubscriptionPayment $payment, string $callbackUrl): string;

    /**
     * Confirm the payment with the gateway (authoritative, server-side) after
     * the user returns to the callback. Returns true when actually paid.
     */
    public function verify(Request $request, SubscriptionPayment $payment): bool;

    public function name(): string;
}
