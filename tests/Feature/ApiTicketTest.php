<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTicketTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected Department $dept;
    protected User $head;
    protected User $tech;
    protected User $requester;
    protected TicketWorkflowService $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workflow = app(TicketWorkflowService::class);

        $this->company = Company::create(['name' => 'API T', 'code' => 'AT' . rand(1000, 9999)]);
        $this->dept = Department::create(['company_id' => $this->company->id, 'name' => 'Maint', 'type' => 'maintenance']);
        $this->head = $this->user(User::ROLE_DEPARTMENT_HEAD);
        $this->dept->update(['head_id' => $this->head->id]);
        $this->tech = $this->user(User::ROLE_TECHNICIAN);
        $this->requester = $this->user(User::ROLE_REQUESTER);
    }

    protected function user(string $role): User
    {
        $u = User::create([
            'company_id' => $this->company->id,
            'department_id' => $this->dept->id,
            'name' => $role . uniqid(),
            'email' => uniqid() . '@apit.local',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);
        $u->assignRole($role);

        return $u;
    }

    protected function makeTicket(User $creator): Ticket
    {
        return $this->workflow->create(
            ['company_id' => $this->company->id, 'title' => 'T' . uniqid(), 'department_id' => $this->dept->id],
            $creator,
        );
    }

    public function test_index_is_role_scoped_to_requesters_own_tickets(): void
    {
        $this->makeTicket($this->requester);
        $this->makeTicket($this->requester);
        $this->makeTicket($this->head); // someone else's

        Sanctum::actingAs($this->requester);
        $res = $this->getJson('/api/v1/tickets')->assertOk();

        $this->assertCount(2, $res->json('data'));
    }

    public function test_requester_can_create_ticket(): void
    {
        Sanctum::actingAs($this->requester);

        $res = $this->postJson('/api/v1/tickets', [
            'title' => 'مكيف لا يعمل',
            'description' => 'تسريب',
            'department_id' => $this->dept->id,
        ])->assertCreated();

        $this->assertEquals('open', $res->json('data.status'));
        $this->assertNotEmpty($res->json('data.number'));
    }

    public function test_show_exposes_permissions_and_available_actions(): void
    {
        $ticket = $this->makeTicket($this->requester);

        // Head sees an "assign" action; requester does not.
        Sanctum::actingAs($this->head);
        $res = $this->getJson("/api/v1/tickets/{$ticket->id}")->assertOk();
        $this->assertContains('assign', $res->json('data.available_actions'));
        $this->assertTrue($res->json('data.permissions.assign'));

        Sanctum::actingAs($this->requester);
        $res = $this->getJson("/api/v1/tickets/{$ticket->id}")->assertOk();
        $this->assertNotContains('assign', $res->json('data.available_actions'));
    }

    public function test_full_lifecycle_through_api(): void
    {
        $ticket = $this->makeTicket($this->requester);

        Sanctum::actingAs($this->head);
        $this->postJson("/api/v1/tickets/{$ticket->id}/assign", ['technician_id' => $this->tech->id])
            ->assertOk()->assertJsonPath('data.status', 'assigned');

        Sanctum::actingAs($this->tech);
        $this->postJson("/api/v1/tickets/{$ticket->id}/accept")->assertJsonPath('data.status', 'accepted');
        $this->postJson("/api/v1/tickets/{$ticket->id}/start")->assertJsonPath('data.status', 'in_progress');
        $this->postJson("/api/v1/tickets/{$ticket->id}/progress", ['progress' => 50])
            ->assertJsonPath('data.progress', 50);
        $this->postJson("/api/v1/tickets/{$ticket->id}/resolve", ['resolution_note' => 'تم'])
            ->assertJsonPath('data.status', 'resolved');

        Sanctum::actingAs($this->head);
        $this->postJson("/api/v1/tickets/{$ticket->id}/approve")
            ->assertOk()->assertJsonPath('data.status', 'closed');
    }

    public function test_technician_cannot_assign(): void
    {
        $ticket = $this->makeTicket($this->requester);

        Sanctum::actingAs($this->tech);
        $this->postJson("/api/v1/tickets/{$ticket->id}/assign", ['technician_id' => $this->tech->id])
            ->assertForbidden();
    }

    public function test_comment_is_recorded(): void
    {
        $ticket = $this->makeTicket($this->requester);

        Sanctum::actingAs($this->requester);
        $res = $this->postJson("/api/v1/tickets/{$ticket->id}/comment", ['body' => 'متى يُصلح؟'])
            ->assertOk();

        $this->assertNotEmpty(collect($res->json('data.comments'))->firstWhere('body', 'متى يُصلح؟'));
    }

    public function test_meta_includes_dynamic_example_per_department(): void
    {
        Sanctum::actingAs($this->requester);
        $res = $this->getJson('/api/v1/tickets/meta')->assertOk();

        $dept = collect($res->json('departments'))->firstWhere('id', $this->dept->id);
        $this->assertNotNull($dept);
        $this->assertEquals('maintenance', $dept['type']);
        $this->assertEquals(config('ticket_examples.maintenance'), $dept['example']);
    }

    public function test_dashboard_stats(): void
    {
        $this->makeTicket($this->requester);

        Sanctum::actingAs($this->requester);
        $this->getJson('/api/v1/dashboard/stats')
            ->assertOk()
            ->assertJsonStructure(['total', 'open', 'in_progress', 'overdue', 'by_status']);
    }
}
