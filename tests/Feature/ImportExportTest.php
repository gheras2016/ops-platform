<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportExportTest extends TestCase
{
    use DatabaseTransactions;

    protected function company(): Company
    {
        return Company::create(['name' => 'IE Co', 'code' => 'IE' . rand(1000, 9999)]);
    }

    protected function admin(Company $company): User
    {
        $u = User::create(['company_id' => $company->id, 'name' => 'Admin', 'email' => uniqid() . '@ie.local', 'password' => bcrypt('password')]);
        $u->assignRole(User::ROLE_COMPANY_ADMIN);

        return $u;
    }

    public function test_admin_can_import_spare_parts_from_csv(): void
    {
        $company = $this->company();
        $admin = $this->admin($company);

        $csv = "name,part_number,category,quantity,min_stock,unit_price\n"
             . "مضخة اختبار,PN-IMP-1,مضخات,15,3,250\n"
             . "صمام اختبار,PN-IMP-2,صمامات,8,2,90\n";
        $file = UploadedFile::fake()->createWithContent('parts.csv', $csv);

        $this->actingAs($admin)->post('/spare-parts/import', ['file' => $file])->assertRedirect();

        $this->assertDatabaseHas('spare_parts', ['part_number' => 'PN-IMP-1', 'company_id' => $company->id, 'quantity' => 15]);
        $this->assertDatabaseHas('spare_parts', ['part_number' => 'PN-IMP-2', 'company_id' => $company->id]);
        // category auto-created
        $this->assertDatabaseHas('spare_categories', ['name' => 'مضخات', 'company_id' => $company->id]);
    }

    public function test_report_exports_return_files(): void
    {
        $company = $this->company();
        $admin = $this->admin($company);

        $this->actingAs($admin)->get('/reports/export/pdf')
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)->get('/reports/export/xlsx')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($admin)->get('/reports/export/csv')->assertOk();
    }

    public function test_department_head_report_is_scoped_to_their_department(): void
    {
        $company = $this->company();
        $deptA = Department::create(['company_id' => $company->id, 'name' => 'Dept A', 'type' => 'it']);
        $deptB = Department::create(['company_id' => $company->id, 'name' => 'Dept B', 'type' => 'maintenance']);

        $head = User::create(['company_id' => $company->id, 'department_id' => $deptA->id, 'name' => 'Head A', 'email' => uniqid() . '@ie.local', 'password' => bcrypt('password')]);
        $head->assignRole(User::ROLE_DEPARTMENT_HEAD);
        $deptA->update(['head_id' => $head->id]);

        $ticketA = $this->makeTicket($company, $deptA, 'AAA-ticket');
        $ticketB = $this->makeTicket($company, $deptB, 'BBB-ticket');

        $csv = $this->actingAs($head)->get('/reports/export/csv')->streamedContent();

        $this->assertStringContainsString($ticketA->ticket_number, $csv);
        $this->assertStringNotContainsString($ticketB->ticket_number, $csv);
    }

    protected function makeTicket(Company $company, Department $dept, string $title): Ticket
    {
        return Ticket::create([
            'company_id' => $company->id,
            'ticket_number' => 'TKT-' . uniqid(),
            'title' => $title,
            'department_id' => $dept->id,
            'status' => Ticket::STATUS_OPEN,
            'progress' => 0,
        ]);
    }
}
