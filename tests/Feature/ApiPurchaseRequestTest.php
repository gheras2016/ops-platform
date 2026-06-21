<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\SparePart;
use App\Models\User;
use App\Services\ProcurementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiPurchaseRequestTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected Department $dept;
    protected User $head;
    protected User $finance;
    protected User $warehouse;
    protected User $tech;
    protected SparePart $part;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'PR', 'code' => 'PR' . rand(1000, 9999)]);
        $this->dept = Department::create(['company_id' => $this->company->id, 'name' => 'Elec', 'type' => 'electrical']);
        $this->head = $this->user(User::ROLE_DEPARTMENT_HEAD, $this->dept->id);
        $this->dept->update(['head_id' => $this->head->id]);
        $this->finance = $this->user(User::ROLE_FINANCE_MANAGER, null);
        $this->warehouse = $this->user(User::ROLE_WAREHOUSE_MANAGER, null);
        $this->tech = $this->user(User::ROLE_TECHNICIAN, $this->dept->id);

        $this->part = SparePart::create([
            'company_id' => $this->company->id, 'name' => 'Breaker',
            'part_number' => 'PN-' . uniqid(), 'quantity' => 0, 'unit_price' => 100,
        ]);
    }

    protected function user(string $role, ?int $deptId): User
    {
        $u = User::create([
            'company_id' => $this->company->id,
            'department_id' => $deptId,
            'name' => $role . uniqid(),
            'email' => uniqid() . '@pr.local',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);
        $u->assignRole($role);

        return $u;
    }

    /** Warehouse raises a request for the electrical dept (headed by $head). */
    protected function createRequest(): PurchaseRequest
    {
        $pr = app(ProcurementService::class)->createManual($this->warehouse, [
            'department_id' => $this->dept->id,
            'fulfillment_type' => PurchaseRequest::TYPE_STOCK,
        ], [['spare_part_id' => $this->part->id, 'quantity' => 5, 'unit_price' => 100]]);
        app(ProcurementService::class)->submit($pr, $this->warehouse);

        return $pr->refresh();
    }

    public function test_create_via_api_starts_chain_at_department_head(): void
    {
        Sanctum::actingAs($this->warehouse);
        $res = $this->postJson('/api/v1/purchase-requests', [
            'department_id' => $this->dept->id,
            'fulfillment_type' => 'stock',
            'items' => [['spare_part_id' => $this->part->id, 'quantity' => 5, 'unit_price' => 100]],
        ])->assertCreated();

        $this->assertEquals('pending_dept', $res->json('data.status'));
        $this->assertEquals(500.0, $res->json('data.total_estimate')); // 5 * 100
    }

    public function test_full_chain_head_then_finance_then_receive_adds_stock(): void
    {
        $pr = $this->createRequest();

        // Department head approves → pending finance.
        Sanctum::actingAs($this->head);
        $this->postJson("/api/v1/purchase-requests/{$pr->id}/approve")
            ->assertOk()->assertJsonPath('data.status', 'pending_finance');

        // Finance approves → approved.
        Sanctum::actingAs($this->finance);
        $this->postJson("/api/v1/purchase-requests/{$pr->id}/approve")
            ->assertOk()->assertJsonPath('data.status', 'approved');

        // Warehouse receives → stock in.
        Sanctum::actingAs($this->warehouse);
        $this->postJson("/api/v1/purchase-requests/{$pr->id}/receive")
            ->assertOk()->assertJsonPath('data.status', 'received');

        $this->assertEquals(5, $this->part->fresh()->quantity); // 0 + 5
        $this->assertDatabaseHas('stock_transactions', [
            'spare_part_id' => $this->part->id, 'type' => 'in', 'quantity' => 5,
        ]);
    }

    public function test_head_can_reject(): void
    {
        $pr = $this->createRequest();

        Sanctum::actingAs($this->head);
        $this->postJson("/api/v1/purchase-requests/{$pr->id}/reject", ['reason' => 'غير ضروري'])
            ->assertOk()->assertJsonPath('data.status', 'rejected');
    }

    public function test_non_decider_cannot_approve(): void
    {
        $pr = $this->createRequest();

        Sanctum::actingAs($this->tech);
        $this->postJson("/api/v1/purchase-requests/{$pr->id}/approve")->assertForbidden();

        // Finance can't act while it's still at the department stage.
        Sanctum::actingAs($this->finance);
        $this->postJson("/api/v1/purchase-requests/{$pr->id}/approve")->assertForbidden();
    }

    public function test_inbox_is_role_aware_through_the_chain(): void
    {
        $pr = $this->createRequest();

        // Stage 1: head sees it actionable with approve/reject.
        Sanctum::actingAs($this->head);
        $res = $this->getJson('/api/v1/purchase-requests')->assertOk();
        $this->assertContains($pr->id, collect($res->json('actionable'))->pluck('id'));
        $this->assertContains('approve', $res->json('actionable.0.available_actions'));

        // Finance sees nothing actionable yet.
        Sanctum::actingAs($this->finance);
        $this->assertEmpty($this->getJson('/api/v1/purchase-requests')->json('actionable'));

        // Advance to finance.
        app(ProcurementService::class)->approve($pr->fresh(), $this->head);
        Sanctum::actingAs($this->finance);
        $res = $this->getJson('/api/v1/purchase-requests')->assertOk();
        $this->assertContains($pr->id, collect($res->json('actionable'))->pluck('id'));

        // Advance to approved → warehouse can receive.
        app(ProcurementService::class)->approve($pr->fresh(), $this->finance);
        Sanctum::actingAs($this->warehouse);
        $res = $this->getJson('/api/v1/purchase-requests')->assertOk();
        $this->assertContains('receive', $res->json('actionable.0.available_actions'));
    }

    public function test_requester_sees_own_request_in_mine(): void
    {
        $pr = $this->createRequest(); // requested by warehouse

        Sanctum::actingAs($this->warehouse);
        $res = $this->getJson('/api/v1/purchase-requests')->assertOk();
        $this->assertContains($pr->id, collect($res->json('mine'))->pluck('id'));
    }
}
