<?php

namespace App\Services\Payments;

use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Stripe gateway (international card payments) via Checkout Sessions. Create a
 * hosted session, redirect to its URL, then confirm by reading the session's
 * payment_status on return.
 *
 * Requires STRIPE_SECRET (and PAYMENT_GATEWAY=stripe). Amounts are in the minor
 * unit (cents) — price * 100.
 */
class StripeGateway implements PaymentGateway
{
    private const BASE = 'https://api.stripe.com/v1';

    public function checkout(SubscriptionPayment $payment, string $callbackUrl): string
    {
        $secret = (string) config('payments.stripe.secret');
        $this->guard($secret !== '', 'لم تُضبط مفاتيح بوابة الدفع (Stripe).');

        $res = Http::withToken($secret)->asForm()->post(self::BASE . '/checkout/sessions', [
            'mode' => 'payment',
            'success_url' => $callbackUrl,
            'cancel_url' => $callbackUrl,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower((string) $payment->currency),
                    'unit_amount' => (int) round((float) $payment->amount * 100),
                    'product_data' => [
                        'name' => 'اشتراك ' . ($payment->plan?->name ?? '') . ' — ' . ($payment->company?->name ?? ''),
                    ],
                ],
            ]],
        ]);

        $this->guard($res->successful() && $res->json('url'), 'تعذّر بدء عملية الدفع. حاول مجدداً.');

        $payment->update(['reference' => $res->json('id')]);

        return $res->json('url');
    }

    public function verify(Request $request, SubscriptionPayment $payment): bool
    {
        $secret = (string) config('payments.stripe.secret');
        if (! $payment->reference || $secret === '') {
            return false;
        }

        $res = Http::withToken($secret)->get(self::BASE . '/checkout/sessions/' . $payment->reference);

        return $res->successful() && $res->json('payment_status') === 'paid';
    }

    public function name(): string
    {
        return 'stripe';
    }

    private function guard(bool $ok, string $message): void
    {
        if (! $ok) {
            throw ValidationException::withMessages(['payment' => $message]);
        }
    }
}
