<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

/**
 * Platform (super-admin) subscription management: overview KPIs + per-tenant
 * actions (activate/renew, extend trial, suspend). Gated by `platform-access`.
 */
class SubscriptionAdminController extends Controller
{
    public function __construct(protected SubscriptionService $subscriptions)
    {
        $this->middleware('can:platform-access');
    }

    public function index()
    {
        $counts = Company::selectRaw('subscription_status, count(*) as total')
            ->groupBy('subscription_status')->pluck('total', 'subscription_status');

        // Monthly-equivalent recurring revenue from active tenants.
        $mrr = Company::where('subscription_status', Company::SUB_ACTIVE)
            ->with('plan')->get()
            ->sum(function ($c) {
                if (! $c->plan) {
                    return 0;
                }

                return $c->plan->billing_period === Plan::PERIOD_MONTHLY
                    ? (float) $c->plan->price
                    : (float) $c->plan->price / 12;
            });

        // Most-urgent first: soonest deadline (nulls last).
        $companies = Company::with('plan')
            ->orderByRaw('COALESCE(current_period_end, trial_ends_at) is null')
            ->orderByRaw('COALESCE(current_period_end, trial_ends_at) asc')
            ->paginate(20);

        return view('subscriptions.index', [
            'companies' => $companies,
            'plans' => Plan::active()->orderBy('sort')->get(),
            'counts' => $counts,
            'mrr' => $mrr,
            'total' => Company::count(),
        ]);
    }

    /** Activate or renew a paid subscription (records the payment). */
    public function activate(Request $request, Company $company)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'method' => ['nullable', 'in:online,manual'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $plan = Plan::findOrFail($data['plan_id']);
        $this->subscriptions->subscribe($company, $plan, [
            'method' => $data['method'] ?? 'manual',
            'reference' => $data['reference'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', "تم تفعيل اشتراك «{$company->name}» بباقة {$plan->name}.");
    }

    public function extendTrial(Request $request, Company $company)
    {
        $data = $request->validate(['days' => ['required', 'integer', 'min:1', 'max:365']]);
        $this->subscriptions->extendTrial($company, $data['days']);

        return back()->with('success', "تم تمديد تجربة «{$company->name}» {$data['days']} يوماً.");
    }

    public function suspend(Company $company)
    {
        $this->subscriptions->suspend($company);

        return back()->with('success', "تم إيقاف «{$company->name}».");
    }
}
