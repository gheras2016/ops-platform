<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\PartRequest;
use App\Models\SparePart;
use App\Models\Ticket;
use App\Models\User;
use App\Services\PartRequestWorkflowService;
use App\Services\TicketWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiPartRequestApprovalTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected Department $dept;
    protected User $head;
    protected User $tech;
    protected User $warehouse;
    protected SparePart $part;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'PRA', 'code' => 'PRA' . rand(1000, 9999)]);
        $this->dept = Department::create(['company_id' => $this->company->id, 'name' => 'Maint', 'type' => 'maintenance']);
        $this->head = $this->user(User::ROLE_DEPARTMENT_HEAD);
        $this->dept->update(['head_id' => $this->head->id]);
        $this->tech = $this->user(User::ROLE_TECHNICIAN);
        $this->warehouse = $this->user(User::ROLE_WAREHOUSE_MANAGER);

        $this->part = SparePart::create([
            'company_id' => $this->company->id,
            'name' => 'Bearing',
            'part_number' => 'PN-' . uniqid(),
            'quantity' => 10,
            'min_stock' => 1,
            'unit_price' => 30,
        ]);

        $workflow = app(TicketWorkflowService::class);
        $this->ticket = $workflow->create(
            ['company_id' => $this->company->id, 'title' => 'T', 'department_id' => $this->dept->id],
            $this->tech,
        );
        $workflow->assign($this->ticket->fresh(), $this->tech, $this->head);
        $workflow->accept($this->ticket->fresh(), $this->tech);
        $workflow->start($this->ticket->fresh(), $this->tech);
    }

    protected function user(string $role): User
    {
        $u = User::create([
            'company_id' => $this->company->id,
            'department_id' => $this->dept->id,
            'name' => $role . uniqid(),
            'email' => uniqid() . '@pra.local',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);
        $u->assignRole($role);

        return $u;
    }

    /** A pending catalogue request raised by the technician (pauses the ticket). */
    protected function pendingRequest(int $qty = 2): PartRequest
    {
        return app(PartRequestWorkflowService::class)->create(
            $this->ticket->fresh(),
            $this->tech,
            [['spare_part_id' => $this->part->id, 'quantity' => $qty]],
        );
    }

    public function test_head_approves_then_warehouse_issues_and_stock_drops(): void
    {
        $pr = $this->pendingRequest(3);

        // Head approves.
        Sanctum::actingAs($this->head);
        $this->postJson("/api/v1/part-requests/{$pr->id}/approve")
            ->assertOk()->assertJsonPath('data.status', 'approved');

        // Warehouse issues → stock deducted + mirrored onto the ticket.
        Sanctum::actingAs($this->warehouse);
        $this->postJson("/api/v1/part-requests/{$pr->id}/issue")
            ->assertOk()->assertJsonPath('data.status', 'issued');

        $this->assertEquals(7, $this->part->fresh()->quantity); // 10 - 3
        $this->assertDatabaseHas('stock_transactions', [
            'spare_part_id' => $this->part->id, 'type' => 'out', 'related_ticket_id' => $this->ticket->id,
        ]);
        $this->assertDatabaseHas('ticket_spare_parts', [
            'ticket_id' => $this->ticket->id, 'spare_part_id' => $this->part->id,
        ]);
    }

    public function test_head_can_reject(): void
    {
        $pr = $this->pendingRequest();

        Sanctum::actingAs($this->head);
        $this->postJson("/api/v1/part-requests/{$pr->id}/reject", ['reason' => 'غير مبرّر'])
            ->assertOk()->assertJsonPath('data.status', 'rejected');
    }

    public function test_technician_cannot_approve(): void
    {
        $pr = $this->pendingRequest();

        Sanctum::actingAs($this->tech);
        $this->postJson("/api/v1/part-requests/{$pr->id}/approve")->assertForbidden();
    }

    public function test_warehouse_cannot_approve_only_issue(): void
    {
        $pr = $this->pendingRequest();

        Sanctum::actingAs($this->warehouse);
        // Cannot approve a pending request...
        $this->postJson("/api/v1/part-requests/{$pr->id}/approve")->assertForbidden();
    }

    public function test_inbox_is_role_aware(): void
    {
        $pr = $this->pendingRequest();

        // Head sees it under pending approvals, with approve/reject actions.
        Sanctum::actingAs($this->head);
        $res = $this->getJson('/api/v1/part-requests')->assertOk();
        $this->assertNotEmpty($res->json('pending_approvals'));
        $this->assertContains('approve', $res->json('pending_approvals.0.available_actions'));

        // Warehouse sees nothing to issue yet (still pending).
        Sanctum::actingAs($this->warehouse);
        $res = $this->getJson('/api/v1/part-requests')->assertOk();
        $this->assertEmpty($res->json('to_issue'));

        // After approval it moves to the warehouse queue.
        app(PartRequestWorkflowService::class)->approve($pr->fresh(), $this->head);
        Sanctum::actingAs($this->warehouse);
        $res = $this->getJson('/api/v1/part-requests')->assertOk();
        $this->assertNotEmpty($res->json('to_issue'));
        $this->assertContains('issue', $res->json('to_issue.0.available_actions'));
    }

    public function test_requester_cannot_open_inbox(): void
    {
        $requester = $this->user(User::ROLE_REQUESTER);
        Sanctum::actingAs($requester);
        $this->getJson('/api/v1/part-requests')->assertForbidden();
    }
}
