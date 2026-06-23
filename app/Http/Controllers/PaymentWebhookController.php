<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPayment;
use App\Services\Payments\PaymentManager;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

/**
 * Async payment confirmation from the gateway (server-to-server). Independent of
 * the browser callback, so a subscription still activates even if the customer
 * closes the tab after paying. Public + CSRF-exempt; security comes from
 * server-side verification with the gateway (verify()).
 */
class PaymentWebhookController extends Controller
{
    public function handle(Request $request, string $gateway, PaymentManager $payments, SubscriptionService $subscriptions)
    {
        try {
            $driver = $payments->driver($gateway);
        } catch (\Throwable $e) {
            return response()->json(['ignored' => true], 200);
        }

        $reference = $driver->webhookReference($request);
        if (! $reference) {
            return response()->json(['ignored' => true], 200);
        }

        $payment = SubscriptionPayment::where('gateway', $gateway)
            ->where('reference', $reference)
            ->first();

        // Verify authoritatively with the gateway before activating.
        if ($payment && ! $payment->isPaid() && $driver->verify($request, $payment)) {
            $subscriptions->confirmPayment($payment);
        }

        // Always 200 so the gateway doesn't retry a handled/irrelevant event.
        return response()->json(['ok' => true], 200);
    }
}
