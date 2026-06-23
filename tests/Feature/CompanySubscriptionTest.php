<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CompanySubscriptionTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected User $admin;
    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create(['name' => 'Acme', 'code' => 'AC' . rand(1000, 9999)]);
        $this->admin = $this->user(User::ROLE_COMPANY_ADMIN);
        $this->plan = Plan::create([
            'name' => 'Yearly', 'slug' => 'y-' . uniqid(), 'price' => 1999,
            'billing_period' => Plan::PERIOD_YEARLY, 'duration_days' => 365,
            'features' => ['ميزة'],
        ]);
    }

    protected function user(string $role): User
    {
        $u = User::create([
            'company_id' => $this->company->id, 'name' => $role,
            'email' => uniqid() . '@x.test', 'password' => bcrypt('secret123'), 'is_active' => true,
        ]);
        $u->assignRole($role);

        return $u;
    }

    public function test_company_admin_sees_self_service_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('company.subscription'))
            ->assertOk()->assertSee('اختر باقة');
    }

    public function test_online_checkout_then_callback_activates_subscription(): void
    {
        // Sandbox gateway (PAYMENT_GATEWAY=test) — checkout creates a pending payment
        // and redirects; the callback (result=paid) confirms + activates.
        $this->actingAs($this->admin)
            ->post(route('company.subscription.checkout'), ['plan_id' => $this->plan->id])
            ->assertRedirect();

        $payment = SubscriptionPayment::where('company_id', $this->company->id)->latest()->firstOrFail();
        $this->assertEquals('pending', $payment->status);

        $this->actingAs($this->admin)
            ->get(route('company.subscription.callback', $payment) . '?result=paid')
            ->assertRedirect(route('company.subscription'));

        $this->assertEquals('paid', $payment->refresh()->status);
        $this->assertEquals(Company::SUB_ACTIVE, $this->company->refresh()->subscription_status);
        $this->assertEqualsWithDelta(365, $this->company->daysRemaining(), 1);
    }

    public function test_callback_rejects_another_companys_payment(): void
    {
        $other = Company::create(['name' => 'Other', 'code' => 'OT' . rand(1000, 9999)]);
        $payment = $other->payments()->create([
            'plan_id' => $this->plan->id, 'amount' => 1999, 'status' => 'pending', 'method' => 'online', 'gateway' => 'test',
        ]);

        $this->actingAs($this->admin)
            ->get(route('company.subscription.callback', $payment) . '?result=paid')
            ->assertForbidden();
    }

    public function test_non_admin_cannot_access(): void
    {
        $tech = $this->user(User::ROLE_TECHNICIAN);
        $this->actingAs($tech)->get(route('company.subscription'))->assertForbidden();
    }

    public function test_webhook_confirms_payment_server_to_server(): void
    {
        $svc = app(\App\Services\SubscriptionService::class);
        $payment = $svc->createPendingPayment($this->company, $this->plan, 'test');
        $payment->update(['reference' => 'test_' . $payment->id]);

        // No auth/session — the gateway calls this directly.
        $this->postJson('/api/payments/webhook/test', [
            'reference' => 'test_' . $payment->id,
            'result' => 'paid',
        ])->assertOk();

        $this->assertEquals('paid', $payment->refresh()->status);
        $this->assertEquals(Company::SUB_ACTIVE, $this->company->refresh()->subscription_status);
    }
}
