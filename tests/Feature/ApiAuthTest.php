<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeUser(bool $companyActive = true, bool $userActive = true): User
    {
        $company = Company::create(['name' => 'API Co', 'code' => 'API' . rand(1000, 9999), 'is_active' => $companyActive]);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'API User',
            'email' => uniqid() . '@api.local',
            'password' => bcrypt('secret123'),
            'is_active' => $userActive,
        ]);
        $user->assignRole(User::ROLE_COMPANY_ADMIN);

        return $user;
    }

    public function test_login_returns_token_and_profile(): void
    {
        $user = $this->makeUser();

        $res = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
            'device_name' => 'pixel',
        ]);

        $res->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'company' => ['id', 'name'], 'roles', 'abilities']]);

        $this->assertNotEmpty($res->json('token'));
        $this->assertContains('admin-access', $res->json('user.abilities'));
        $this->assertContains('company_admin', $res->json('user.roles'));
    }

    public function test_login_rejects_bad_credentials(): void
    {
        $user = $this->makeUser();

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertStatus(422);
    }

    public function test_login_blocks_deactivated_company(): void
    {
        $user = $this->makeUser(companyActive: false);

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertStatus(422);
    }

    public function test_me_requires_token_and_returns_profile(): void
    {
        $user = $this->makeUser();

        $this->getJson('/api/v1/auth/me')->assertStatus(401);

        $token = $user->createToken('test')->plainTextToken;
        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_logout_revokes_token(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        // The token row is revoked (deleted) — definitive proof of logout.
        // Scope to this user's tokens so the assertion is isolation-safe.
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }
}
