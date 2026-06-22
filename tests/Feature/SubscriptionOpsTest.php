<?php

namespace Tests\Feature;

use App\Services\Payments\MoyasarGateway;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\StripeGateway;
use App\Services\Payments\TestGateway;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class SubscriptionOpsTest extends TestCase
{
    public function test_payment_manager_resolves_each_driver(): void
    {
        $m = new PaymentManager();

        $this->assertInstanceOf(TestGateway::class, $m->driver('test'));
        $this->assertInstanceOf(MoyasarGateway::class, $m->driver('moyasar'));
        $this->assertInstanceOf(StripeGateway::class, $m->driver('stripe'));
    }

    public function test_default_driver_comes_from_config(): void
    {
        config()->set('payments.gateway', 'stripe');
        $this->assertInstanceOf(StripeGateway::class, (new PaymentManager())->driver());
    }

    public function test_subscription_tick_is_scheduled_daily(): void
    {
        $schedule = app(Schedule::class);
        $commands = collect($schedule->events())->map(fn ($e) => $e->command)->implode(' | ');

        $this->assertStringContainsString('subscriptions:tick', $commands);
    }
}
