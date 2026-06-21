<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Notifications\SubscriptionNotification;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Daily subscription tick: send expiry reminders (7/3/1 days), move expired
 * tenants into grace, and suspend those past their grace window. Schedule this
 * via the app scheduler (see Console\Kernel) backed by a cron running
 * `php artisan schedule:run` every minute.
 */
class RunSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:tick';

    protected $description = 'Process tenant subscription reminders and expiry/suspension transitions';

    public function handle(SubscriptionService $service): int
    {
        $companies = Company::whereNotIn('subscription_status', [Company::SUB_SUSPENDED, Company::SUB_CANCELLED])
            ->where(fn ($q) => $q->whereNotNull('current_period_end')->orWhereNotNull('trial_ends_at'))
            ->get();

        $reminders = 0;
        $graced = 0;
        $suspended = 0;

        foreach ($companies as $company) {
            // Reminder first (uses the still-valid days-remaining).
            if ($days = $service->reminderThreshold($company)) {
                $this->notifyAdmins($company, 'expiring',
                    "ينتهي اشتراك «{$company->name}» خلال {$days} يوم — جدّد لتجنّب الإيقاف.", $days);
                $reminders++;
            }

            $new = $service->processExpiry($company);
            if ($new === Company::SUB_GRACE) {
                $this->notifyAdmins($company, 'grace',
                    "انتهى اشتراك «{$company->name}» — لديك مهلة سماح {$company->grace_days} أيام قبل الإيقاف.");
                $graced++;
            } elseif ($new === Company::SUB_SUSPENDED) {
                $this->notifyAdmins($company, 'suspended',
                    "تم إيقاف حساب «{$company->name}» لانتهاء الاشتراك. يرجى التجديد لإعادة التفعيل.");
                $suspended++;
            }
        }

        $this->info("subscriptions:tick — reminders: {$reminders}, grace: {$graced}, suspended: {$suspended}");

        return self::SUCCESS;
    }

    private function notifyAdmins(Company $company, string $event, string $message, ?int $days = null): void
    {
        $admins = User::where('company_id', $company->id)->role(User::ROLE_COMPANY_ADMIN)->get();
        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new SubscriptionNotification($company, $event, $message, $days));
    }
}
