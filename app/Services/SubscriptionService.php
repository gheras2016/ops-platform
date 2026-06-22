<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\DB;

/**
 * Tenant subscription lifecycle:
 *   trial → active (paid) → grace (expired, still works) → suspended (blocked).
 *
 * Suspension flips company.is_active=false, so the existing EnsureActiveAccount
 * middleware blocks access on the very next request — no new gating needed.
 */
class SubscriptionService
{
    /** Days before the deadline at which a reminder is sent. */
    public const REMINDER_DAYS = [7, 3, 1];

    /** Start (or restart) a free trial. */
    public function startTrial(Company $company, int $days = 7): Company
    {
        $company->update([
            'subscription_status' => Company::SUB_TRIAL,
            'trial_ends_at' => now()->addDays($days),
            'current_period_end' => null,
            'is_active' => true,
        ]);

        return $company;
    }

    /**
     * Activate / renew a paid subscription and record the payment. Renewals
     * stack onto an unexpired period. $opts: amount, method, gateway, reference,
     * created_by.
     */
    public function subscribe(Company $company, Plan $plan, array $opts = []): SubscriptionPayment
    {
        return DB::transaction(function () use ($company, $plan, $opts) {
            $base = $company->current_period_end && $company->current_period_end->isFuture()
                ? $company->current_period_end->copy()
                : now();
            $periodEnd = $base->copy()->addDays($plan->duration_days);

            $payment = $company->payments()->create([
                'plan_id' => $plan->id,
                'amount' => $opts['amount'] ?? $plan->price,
                'currency' => $opts['currency'] ?? $plan->currency,
                'status' => SubscriptionPayment::STATUS_PAID,
                'method' => $opts['method'] ?? SubscriptionPayment::METHOD_ONLINE,
                'gateway' => $opts['gateway'] ?? null,
                'reference' => $opts['reference'] ?? null,
                'paid_at' => now(),
                'period_start' => now()->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'created_by' => $opts['created_by'] ?? null,
            ]);

            $company->update([
                'subscription_status' => Company::SUB_ACTIVE,
                'plan_id' => $plan->id,
                'current_period_end' => $periodEnd,
                'is_active' => true,
            ]);

            return $payment;
        });
    }

    /** Create a pending (unpaid) payment to drive an online checkout. */
    public function createPendingPayment(Company $company, Plan $plan, string $gateway): SubscriptionPayment
    {
        return $company->payments()->create([
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'currency' => $plan->currency,
            'status' => SubscriptionPayment::STATUS_PENDING,
            'method' => SubscriptionPayment::METHOD_ONLINE,
            'gateway' => $gateway,
        ]);
    }

    /** Mark a pending payment paid and activate/renew the subscription. Idempotent. */
    public function confirmPayment(SubscriptionPayment $payment): void
    {
        if ($payment->isPaid()) {
            return;
        }

        DB::transaction(function () use ($payment) {
            $company = $payment->company;
            $plan = $payment->plan;

            $base = $company->current_period_end && $company->current_period_end->isFuture()
                ? $company->current_period_end->copy()
                : now();
            $periodEnd = $base->copy()->addDays($plan?->duration_days ?? 30);

            $payment->update([
                'status' => SubscriptionPayment::STATUS_PAID,
                'paid_at' => now(),
                'period_start' => now()->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ]);

            $company->update([
                'subscription_status' => Company::SUB_ACTIVE,
                'plan_id' => $plan?->id,
                'current_period_end' => $periodEnd,
                'is_active' => true,
            ]);
        });
    }

    /** Extend (or start) a trial by N days from the later of now / current trial end. */
    public function extendTrial(Company $company, int $days): Company
    {
        $base = $company->trial_ends_at && $company->trial_ends_at->isFuture()
            ? $company->trial_ends_at->copy()
            : now();

        $company->update([
            'subscription_status' => Company::SUB_TRIAL,
            'trial_ends_at' => $base->addDays($days),
            'current_period_end' => null,
            'is_active' => true,
        ]);

        return $company;
    }

    /** Super-admin manually suspends / cancels. */
    public function suspend(Company $company): Company
    {
        $company->update(['subscription_status' => Company::SUB_SUSPENDED, 'is_active' => false]);

        return $company;
    }

    /**
     * Advance a single company through the expiry → grace → suspended states.
     * Returns the NEW status if it changed (so the caller can notify), else null.
     */
    public function processExpiry(Company $company): ?string
    {
        if (in_array($company->subscription_status, [Company::SUB_SUSPENDED, Company::SUB_CANCELLED], true)) {
            return null;
        }

        $end = $company->subscriptionEndsAt();
        if (! $end || now()->lt($end)) {
            return null; // no deadline, or still valid
        }

        $graceEnd = $end->copy()->addDays((int) $company->grace_days);

        // Within the grace window → soft state, still usable.
        if (now()->lte($graceEnd)) {
            if ($company->subscription_status !== Company::SUB_GRACE) {
                $company->update(['subscription_status' => Company::SUB_GRACE]);

                return Company::SUB_GRACE;
            }

            return null;
        }

        // Past grace → hard suspend (blocks login via is_active).
        $company->update(['subscription_status' => Company::SUB_SUSPENDED, 'is_active' => false]);

        return Company::SUB_SUSPENDED;
    }

    /**
     * If the company is approaching its deadline by exactly one of the reminder
     * thresholds, returns that day count (for sending a reminder); else null.
     */
    public function reminderThreshold(Company $company): ?int
    {
        if (! in_array($company->subscription_status, [Company::SUB_TRIAL, Company::SUB_ACTIVE], true)) {
            return null;
        }

        $days = $company->daysRemaining();

        return ($days !== null && in_array($days, self::REMINDER_DAYS, true)) ? $days : null;
    }
}
