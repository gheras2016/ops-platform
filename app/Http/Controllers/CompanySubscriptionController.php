<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use App\Notifications\SubscriptionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

/**
 * Company-admin self-service subscription page: see the current plan/expiry,
 * choose a plan, and request to subscribe. Activation happens either via the
 * online gateway (next phase, when keys are configured) or by the platform
 * super-admin confirming the payment.
 */
class CompanySubscriptionController extends Controller
{
    public function __construct()
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

    /**
     * Request a subscription/renewal for a plan. With the online gateway this
     * will redirect to checkout; for now it notifies the platform admins to
     * confirm the payment (they activate from the subscriptions dashboard).
     */
    public function requestSubscription(Request $request)
    {
        $data = $request->validate(['plan_id' => ['required', 'exists:plans,id']]);
        $company = $request->user()->company;
        abort_unless($company, 404);

        $plan = Plan::findOrFail($data['plan_id']);

        $admins = User::role(User::ROLE_SUPER_ADMIN)->get();
        Notification::send($admins, new SubscriptionNotification(
            $company,
            'request',
            "طلبت «{$company->name}» الاشتراك بباقة {$plan->name} ({$plan->price} {$plan->currency}).",
        ));

        return back()->with('success', 'تم إرسال طلب الاشتراك. سيُفعّل بعد تأكيد الدفع. (الدفع الإلكتروني المباشر قيد التفعيل.)');
    }
}
