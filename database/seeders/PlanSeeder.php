<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'الباقة الشهرية',
                'slug' => 'monthly',
                'price' => 199,
                'billing_period' => Plan::PERIOD_MONTHLY,
                'duration_days' => 30,
                'max_users' => 25,
                'features' => ['البلاغات', 'المخزون', 'طلبات الشراء', 'دعم فني'],
                'sort' => 1,
            ],
            [
                'name' => 'الباقة السنوية',
                'slug' => 'yearly',
                'price' => 1999,
                'billing_period' => Plan::PERIOD_YEARLY,
                'duration_days' => 365,
                'max_users' => null, // unlimited
                'features' => ['كل مزايا الشهرية', 'مستخدمون بلا حد', 'شهران مجاناً', 'أولوية الدعم'],
                'sort' => 2,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan + ['currency' => 'SAR', 'is_active' => true]);
        }
    }
}
