<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Services\Payments\PaymentManager;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Company-admin self-service subscription: view plan/expiry/invoices, and pay
 * online (checkout → gateway hosted page → callback verifies → activate).
 */
class CompanySubscriptionController extends Controller
{
    public function __construct(protected SubscriptionService $subscriptions)
    {
        $this->middleware('can:admin-access');
    }

    public function show(Request $request)
    {
        $company = $request->user()->company;
        abort_unless($company, 404);

        return view('company.subscription', [
            'company' => $company->load('plan'),
            'plans' => Plan::active()->orderBy('sort')->get(),
            'invoices' => $company->payments()->with('plan')->latest()->limit(20)->get(),
        ]);
    }

    /** Start an online checkout for a plan; redirects to the gateway. */
    public function checkout(Request $request, PaymentManager $payments)
    {
        $data = $request->validate(['plan_id' => ['required', 'exists:plans,id']]);
        $company = $request->user()->company;
        abort_unless($company, 404);

        $plan = Plan::findOrFail($data['plan_id']);
        $gateway = $payments->driver();
        $payment = $this->subscriptions->createPendingPayment($company, $plan, $gateway->name());

        try {
            $url = $gateway->checkout($payment, route('company.subscription.callback', $payment));
        } catch (ValidationException $e) {
            $payment->update(['status' => SubscriptionPayment::STATUS_FAILED]);
            throw $e;
        }

        return redirect()->away($url);
    }

    /** Gateway return URL: confirm payment (server-side) and activate. */
    public function callback(Request $request, PaymentManager $payments, SubscriptionPayment $payment)
    {
        abort_unless($payment->company_id === $request->user()->company_id, 403);

        $gateway = $payments->driver($payment->gateway);

        if ($gateway->verify($request, $payment)) {
            $this->subscriptions->confirmPayment($payment);

            return redirect()->route('company.subscription')->with('success', 'تم الدفع وتفعيل اشتراكك بنجاح.');
        }

        $payment->update(['status' => SubscriptionPayment::STATUS_FAILED]);

        return redirect()->route('company.subscription')->withErrors(['payment' => 'لم تكتمل عملية الدفع.']);
    }
}
