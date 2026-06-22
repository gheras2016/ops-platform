<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketWorkflowService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTicketAttachmentTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected User $tech;
    protected User $requester;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->company = Company::create(['name' => 'Att', 'code' => 'AT' . rand(1000, 9999)]);
        $dept = Department::create(['company_id' => $this->company->id, 'name' => 'M', 'type' => 'maintenance']);
        $this->tech = $this->user($dept->id, User::ROLE_TECHNICIAN);
        $this->requester = $this->user($dept->id, User::ROLE_REQUESTER);

        $this->ticket = app(TicketWorkflowService::class)->create(
            ['company_id' => $this->company->id, 'title' => 'T', 'department_id' => $dept->id],
            $this->requester,
        );
    }

    protected function user(int $deptId, string $role): User
    {
        $u = User::create([
            'company_id' => $this->company->id, 'department_id' => $deptId,
            'name' => $role . uniqid(), 'email' => uniqid() . '@att.local',
            'password' => bcrypt('secret123'), 'is_active' => true,
        ]);
        $u->assignRole($role);

        return $u;
    }

    public function test_upload_photo_then_it_appears_in_detail(): void
    {
        Sanctum::actingAs($this->requester);

        $res = $this->postJson("/api/v1/tickets/{$this->ticket->id}/attachments", [
            'files' => [UploadedFile::fake()->image('fault.jpg', 800, 600)],
        ])->assertCreated();

        $first = $res->json('data.0');
        $this->assertTrue($first['is_image']);
        $this->assertNotEmpty($first['url']);

        // The stored file exists on the fake disk.
        $att = $this->ticket->attachments()->first();
        Storage::disk('public')->assertExists($att->path);

        // It shows up on the ticket detail.
        $detail = $this->getJson("/api/v1/tickets/{$this->ticket->id}")->assertOk();
        $this->assertCount(1, $detail->json('data.attachments'));
    }

    public function test_uploader_can_delete_attachment(): void
    {
        Sanctum::actingAs($this->requester);
        $this->postJson("/api/v1/tickets/{$this->ticket->id}/attachments", [
            'files' => [UploadedFile::fake()->image('x.png')],
        ]);
        $att = $this->ticket->attachments()->firstOrFail();

        $this->deleteJson("/api/v1/tickets/{$this->ticket->id}/attachments/{$att->id}")->assertOk();
        $this->assertDatabaseMissing('ticket_attachments', ['id' => $att->id]);
        Storage::disk('public')->assertMissing($att->path);
    }

    public function test_outsider_cannot_attach(): void
    {
        // A requester from another company can't even see the ticket.
        $other = Company::create(['name' => 'Other', 'code' => 'O' . rand(1000, 9999)]);
        $stranger = User::create([
            'company_id' => $other->id, 'name' => 'S', 'email' => uniqid() . '@o.local',
            'password' => bcrypt('secret123'), 'is_active' => true,
        ]);
        $stranger->assignRole(User::ROLE_REQUESTER);

        Sanctum::actingAs($stranger);
        $this->postJson("/api/v1/tickets/{$this->ticket->id}/attachments", [
            'files' => [UploadedFile::fake()->image('x.jpg')],
        ])->assertStatus(404); // ticket out of tenant scope
    }

    public function test_rejects_disallowed_file_type(): void
    {
        Sanctum::actingAs($this->requester);
        $this->postJson("/api/v1/tickets/{$this->ticket->id}/attachments", [
            'files' => [UploadedFile::fake()->create('virus.exe', 10)],
        ])->assertStatus(422);
    }
}
