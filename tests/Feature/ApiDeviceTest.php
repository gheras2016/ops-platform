<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiDeviceTest extends TestCase
{
    use DatabaseTransactions;

    protected function user(): User
    {
        $company = Company::create(['name' => 'Dev', 'code' => 'DV' . rand(1000, 9999)]);
        $u = User::create([
            'company_id' => $company->id, 'name' => 'U', 'email' => uniqid() . '@dev.local',
            'password' => bcrypt('secret123'), 'is_active' => true,
        ]);
        $u->assignRole(User::ROLE_TECHNICIAN);

        return $u;
    }

    public function test_register_device_token(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/devices', ['token' => 'fcm_abc', 'platform' => 'android'])
            ->assertOk()->assertJson(['registered' => true]);

        $this->assertDatabaseHas('device_tokens', ['token' => 'fcm_abc', 'user_id' => $user->id]);
    }

    public function test_registering_same_token_does_not_duplicate(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/devices', ['token' => 'fcm_same']);
        $this->postJson('/api/v1/devices', ['token' => 'fcm_same']);

        $this->assertEquals(1, \App\Models\DeviceToken::where('token', 'fcm_same')->count());
    }

    public function test_unregister_device_token(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/devices', ['token' => 'fcm_del']);

        $this->deleteJson('/api/v1/devices', ['token' => 'fcm_del'])->assertOk();
        $this->assertDatabaseMissing('device_tokens', ['token' => 'fcm_del']);
    }

    public function test_requires_authentication(): void
    {
        $this->postJson('/api/v1/devices', ['token' => 'x'])->assertUnauthorized();
    }
}
