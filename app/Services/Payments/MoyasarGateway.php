<?php

namespace App\Services\Payments;

use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Moyasar gateway (Saudi — supports mada / Visa / Apple Pay, SAR). Uses the
 * Invoices API: create a hosted invoice, redirect the user to its URL, then
 * confirm by fetching the invoice status on return.
 *
 * Requires MOYASAR_SECRET_KEY (and PAYMENT_GATEWAY=moyasar). Amounts are sent in
 * the minor unit (halalas) — price * 100.
 */
class MoyasarGateway implements PaymentGateway
{
    public function checkout(SubscriptionPayment $payment, string $callbackUrl): string
    {
        $secret = (string) config('payments.moyasar.secret_key');
        $base = (string) config('payments.moyasar.base_url');
        $this->guard($secret !== '', 'لم تُضبط مفاتيح بوابة الدفع (Moyasar).');

        $res = Http::withBasicAuth($secret, '')
            ->asForm()
            ->post("{$base}/invoices", [
                'amount' => (int) round((float) $payment->amount * 100),
                'currency' => $payment->currency,
                'description' => 'اشتراك ' . ($payment->plan?->name ?? '') . ' — ' . ($payment->company?->name ?? ''),
                'callback_url' => $callbackUrl,
                'success_url' => $callbackUrl,
            ]);

        $this->guard($res->successful() && $res->json('url'), 'تعذّر بدء عملية الدفع. حاول مجدداً.');

        $payment->update(['reference' => $res->json('id')]);

        return $res->json('url');
    }

    public function verify(Request $request, SubscriptionPayment $payment): bool
    {
        $secret = (string) config('payments.moyasar.secret_key');
        $base = (string) config('payments.moyasar.base_url');
        // Moyasar returns the invoice id on the callback; fall back to the stored reference.
        $invoiceId = $request->query('invoice_id', $payment->reference);
        if (! $invoiceId || $secret === '') {
            return false;
        }

        $res = Http::withBasicAuth($secret, '')->get("{$base}/invoices/{$invoiceId}");

        return $res->successful() && $res->json('status') === 'paid';
    }

    public function name(): string
    {
        return 'moyasar';
    }

    private function guard(bool $ok, string $message): void
    {
        if (! $ok) {
            throw ValidationException::withMessages(['payment' => $message]);
        }
    }
}
