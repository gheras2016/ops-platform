<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected TicketWorkflowService $workflow;
    protected Company $company;
    protected Department $dept;
    protected User $head;
    protected User $tech;
    protected User $requester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workflow = app(TicketWorkflowService::class);

        $this->company = Company::create(['name' => 'Test Co', 'code' => 'TST' . rand(1000, 9999)]);
        $this->dept = Department::create(['company_id' => $this->company->id, 'name' => 'Maint', 'type' => 'maintenance']);
        $this->head = $this->makeUser(User::ROLE_DEPARTMENT_HEAD);
        $this->dept->update(['head_id' => $this->head->id]);
        $this->tech = $this->makeUser(User::ROLE_TECHNICIAN);
        $this->requester = $this->makeUser(User::ROLE_REQUESTER);
        Priority::firstOrCreate(['name' => 'متوسطة'], ['level' => 2]);
    }

    protected function makeUser(string $role): User
    {
        $u = User::create([
            'company_id' => $this->company->id,
            'department_id' => $this->dept->id ?? null,
            'name' => $role . '-' . uniqid(),
            'email' => uniqid() . '@test.local',
            'password' => bcrypt('password'),
        ]);
        $u->assignRole($role);

        return $u;
    }

    public function test_full_lifecycle_open_to_closed(): void
    {
        $ticket = $this->workflow->create([
            'company_id' => $this->company->id,
            'title' => 'Broken AC',
            'department_id' => $this->dept->id,
        ], $this->requester);

        $this->assertEquals(Ticket::STATUS_OPEN, $ticket->status);
        $this->assertNotEmpty($ticket->ticket_number);

        $this->workflow->assign($ticket->fresh(), $this->tech, $this->head);
        $this->assertEquals(Ticket::STATUS_ASSIGNED, $ticket->fresh()->status);
        $this->assertEquals($this->tech->id, $ticket->fresh()->assigned_to);

        $this->workflow->accept($ticket->fresh(), $this->tech);
        $this->assertEquals(Ticket::STATUS_ACCEPTED, $ticket->fresh()->status);

        $this->workflow->start($ticket->fresh(), $this->tech);
        $this->assertEquals(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);

        $this->workflow->pause($ticket->fresh(), $this->tech, 'spare_part', 'awaiting filter');
        $this->assertEquals(Ticket::STATUS_PAUSED, $ticket->fresh()->status);
        $this->assertDatabaseHas('ticket_pause_logs', ['ticket_id' => $ticket->id, 'reason_code' => 'spare_part']);

        $this->workflow->resume($ticket->fresh(), $this->tech);
        $this->assertEquals(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        // the open pause log should now be resumed
        $this->assertNull(\App\Models\TicketPauseLog::where('ticket_id', $ticket->id)->whereNull('resumed_at')->first());

        $this->workflow->resolve($ticket->fresh(), $this->tech, 'replaced filter');
        $this->assertEquals(Ticket::STATUS_RESOLVED, $ticket->fresh()->status);
        $this->assertEquals(100, $ticket->fresh()->progress);

        $this->workflow->approve($ticket->fresh(), $this->head);
        $this->assertEquals(Ticket::STATUS_CLOSED, $ticket->fresh()->status);
        $this->assertEquals($this->head->id, $ticket->fresh()->closed_by);

        // a timeline event for every transition
        $this->assertGreaterThanOrEqual(8, $ticket->events()->count());
    }

    public function test_reject_returns_ticket_to_technician(): void
    {
        $ticket = $this->workflow->create(['company_id' => $this->company->id, 'title' => 'X', 'department_id' => $this->dept->id], $this->requester);
        $this->workflow->assign($ticket->fresh(), $this->tech, $this->head);
        $this->workflow->accept($ticket->fresh(), $this->tech);
        $this->workflow->start($ticket->fresh(), $this->tech);
        $this->workflow->resolve($ticket->fresh(), $this->tech);

        $this->workflow->reject($ticket->fresh(), $this->head, 'still broken');
        $this->assertEquals(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->assertEquals('still broken', $ticket->fresh()->rejected_reason);
    }

    public function test_invalid_transition_is_blocked(): void
    {
        $ticket = $this->workflow->create(['company_id' => $this->company->id, 'title' => 'X', 'department_id' => $this->dept->id], $this->requester);

        // cannot resolve an open (not in-progress) ticket
        $this->expectException(ValidationException::class);
        $this->workflow->resolve($ticket->fresh(), $this->tech);
    }

    public function test_used_parts_are_deducted_from_stock_at_close_not_resolve(): void
    {
        $part = \App\Models\SparePart::create([
            'company_id' => $this->company->id,
            'name' => 'Filter',
            'part_number' => 'PN-' . uniqid(),
            'quantity' => 10,
            'min_stock' => 2,
        ]);

        $ticket = $this->workflow->create(['company_id' => $this->company->id, 'title' => 'Y', 'department_id' => $this->dept->id], $this->requester);
        $this->workflow->assign($ticket->fresh(), $this->tech, $this->head);
        $this->workflow->accept($ticket->fresh(), $this->tech);
        $this->workflow->start($ticket->fresh(), $this->tech);

        $this->workflow->resolve($ticket->fresh(), $this->tech, 'done', [
            ['spare_part_id' => $part->id, 'quantity' => 3],
        ]);

        // Recorded as pending after resolve — stock untouched, no movement yet.
        $this->assertDatabaseHas('ticket_spare_parts', [
            'ticket_id' => $ticket->id, 'spare_part_id' => $part->id, 'quantity_used' => 3, 'deducted_at' => null,
        ]);
        $this->assertEquals(10, $part->fresh()->quantity);
        $this->assertDatabaseMissing('stock_transactions', ['related_ticket_id' => $ticket->id, 'type' => 'out']);

        // Closing (head approval) is when stock actually leaves and the movement is logged.
        $this->workflow->approve($ticket->fresh(), $this->head);

        $this->assertEquals(7, $part->fresh()->quantity); // 10 - 3
        $this->assertDatabaseHas('stock_transactions', ['spare_part_id' => $part->id, 'type' => 'out', 'related_ticket_id' => $ticket->id, 'quantity' => 3]);
        $this->assertNotNull($ticket->fresh()->spareParts->first()->deducted_at);
    }

    public function test_resolve_records_custom_out_of_catalog_used_part(): void
    {
        $ticket = $this->workflow->create(['company_id' => $this->company->id, 'title' => 'C', 'department_id' => $this->dept->id], $this->requester);
        $this->workflow->assign($ticket->fresh(), $this->tech, $this->head);
        $this->workflow->accept($ticket->fresh(), $this->tech);
        $this->workflow->start($ticket->fresh(), $this->tech);

        $this->workflow->resolve($ticket->fresh(), $this->tech, 'fixed', [
            ['custom_name' => 'حساس نادر', 'quantity' => 1, 'unit_cost' => 250],
        ]);

        // Recorded by name with no catalogue link and no stock transaction.
        $this->assertDatabaseHas('ticket_spare_parts', [
            'ticket_id' => $ticket->id, 'spare_part_id' => null, 'custom_name' => 'حساس نادر', 'quantity_used' => 1,
        ]);
        $this->assertDatabaseMissing('stock_transactions', ['related_ticket_id' => $ticket->id, 'type' => 'out']);

        $line = $ticket->fresh()->spareParts->first();
        $this->assertTrue($line->isCustom());
        $this->assertEquals('حساس نادر', $line->displayName());
        $this->assertEquals(250.0, $ticket->fresh()->partsCost());
    }

    public function test_assignment_notifies_the_technician(): void
    {
        $ticket = $this->workflow->create(['company_id' => $this->company->id, 'title' => 'Z', 'department_id' => $this->dept->id], $this->requester);
        $this->workflow->assign($ticket->fresh(), $this->tech, $this->head);

        $this->assertEquals(1, $this->tech->fresh()->unreadNotifications()->count());
        $this->assertEquals('assigned', $this->tech->fresh()->unreadNotifications()->first()->data['event']);
    }
}
