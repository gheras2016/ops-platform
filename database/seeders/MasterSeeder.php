<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Item;
use App\Models\Location;
use App\Models\Priority;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\SparePart;
use App\Models\SpareCategory;
use App\Models\StockTransaction;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\PartRequestWorkflowService;
use App\Services\ProcurementService;
use App\Services\TicketWorkflowService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MasterSeeder extends Seeder
{
    protected TicketWorkflowService $workflow;

    public function run(): void
    {
        $this->workflow = app(TicketWorkflowService::class);

        $this->seedReferenceData();

        // Platform owner (no company => sees all tenants).
        $this->makeUser('مدير المنصة', 'super@ops.test', User::ROLE_SUPER_ADMIN, null);

        // Main demo company (full data).
        $main = Company::create([
            'name' => 'شركة الرواد للصيانة',
            'code' => 'RAWAD',
            'email' => 'info@rawad.test',
            'phone' => '0112345678',
            'address' => 'الرياض، المملكة العربية السعودية',
        ]);
        $this->seedCompany($main, full: true);

        // Second company (proves tenant isolation).
        $second = Company::create([
            'name' => 'مصنع الخليج الصناعي',
            'code' => 'GULF',
            'email' => 'info@gulf.test',
            'phone' => '0133334444',
            'address' => 'الدمام، المملكة العربية السعودية',
            // Distinct branding to showcase per-company visual identity.
            'primary_color' => '#0d9488',
            'sidebar_color' => '#0f2e2a',
            'bg_color' => '#f3faf8',
        ]);
        $this->seedCompany($second, full: false);
    }

    /*
    |--------------------------------------------------------------------------
    | Reference data (shared / global)
    |--------------------------------------------------------------------------
    */
    protected function seedReferenceData(): void
    {
        $priorities = [
            ['name' => 'منخفضة', 'level' => 1, 'color' => 'gray'],
            ['name' => 'متوسطة', 'level' => 2, 'color' => 'blue'],
            ['name' => 'عالية', 'level' => 3, 'color' => 'amber'],
            ['name' => 'عاجلة', 'level' => 4, 'color' => 'orange'],
            ['name' => 'حرجة', 'level' => 5, 'color' => 'red'],
        ];
        foreach ($priorities as $p) {
            Priority::create($p);
        }

        $statuses = [
            ['name' => 'مفتوحة', 'code' => 'open', 'color' => 'gray'],
            ['name' => 'قيد التنفيذ', 'code' => 'in_progress', 'color' => 'amber'],
            ['name' => 'تم الحل', 'code' => 'resolved', 'color' => 'teal'],
            ['name' => 'مغلقة', 'code' => 'closed', 'color' => 'green'],
        ];
        foreach ($statuses as $s) {
            TicketStatus::create($s);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Per-company seeding
    |--------------------------------------------------------------------------
    */
    protected function seedCompany(Company $company, bool $full): void
    {
        $admin = $this->makeUser(
            $full ? 'سعد المدير' : 'مدير الخليج',
            $full ? 'admin@rawad.test' : 'admin@gulf.test',
            User::ROLE_COMPANY_ADMIN,
            $company->id
        );

        // Warehouse / inventory manager (org-wide spare-parts control).
        $this->makeUser(
            $full ? 'وليد أمين المستودع' : 'أمين مستودع الخليج',
            'warehouse@' . strtolower($company->code) . '.test',
            User::ROLE_WAREHOUSE_MANAGER,
            $company->id
        );

        // Finance manager (procurement approval).
        $this->makeUser(
            $full ? 'منى مديرة المالية' : 'مالية الخليج',
            'finance@' . strtolower($company->code) . '.test',
            User::ROLE_FINANCE_MANAGER,
            $company->id
        );

        // Locations — building → floors → rooms, with computed full_path.
        $building = Location::create(['company_id' => $company->id, 'name' => 'المبنى الرئيسي', 'type' => 'building', 'full_path' => 'المبنى الرئيسي']);
        $locations = [$building->id];
        foreach (['الدور الأول', 'الدور الثاني'] as $fi => $floorName) {
            $floor = Location::create([
                'company_id' => $company->id, 'name' => $floorName, 'type' => 'floor',
                'parent_id' => $building->id, 'full_path' => $building->name . ' / ' . $floorName,
            ]);
            $locations[] = $floor->id;
            foreach (['غرفة ' . ($fi * 10 + 1), 'غرفة ' . ($fi * 10 + 2), 'منطقة الخدمات'] as $roomName) {
                $room = Location::create([
                    'company_id' => $company->id, 'name' => $roomName, 'type' => 'room',
                    'parent_id' => $floor->id, 'full_path' => $floor->full_path . ' / ' . $roomName,
                ]);
                $locations[] = $room->id;
            }
        }

        // Parent division (full company) — gives the approval chain a level above heads.
        $divisionId = null;
        if ($full) {
            $division = Department::create([
                'company_id' => $company->id,
                'name' => 'إدارة التشغيل',
                'code' => 'OPS',
                'type' => 'general',
                'color' => 'slate',
                'accepts_tickets' => false, // مستوى إداري للاعتماد فقط
            ]);
            $divisionHead = $this->makeUser('مدير التشغيل', 'ops.head@' . strtolower($company->code) . '.test', User::ROLE_DEPARTMENT_HEAD, $company->id, $division->id);
            $division->update(['head_id' => $divisionHead->id]);
            $divisionId = $division->id;
        }

        // Department blueprint: [name, code, type, color]
        $deptDefs = $full ? [
            ['تقنية المعلومات', 'IT', 'it', 'indigo'],
            ['الصيانة العامة', 'MTN', 'maintenance', 'teal'],
            ['الميكانيكا', 'MEC', 'mechanical', 'amber'],
            ['الكهرباء', 'ELC', 'electrical', 'orange'],
        ] : [
            ['الصيانة', 'MTN', 'maintenance', 'teal'],
        ];

        $departments = [];
        foreach ($deptDefs as $i => [$name, $code, $type, $color]) {
            $dept = Department::create([
                'company_id' => $company->id,
                'name' => $name,
                'code' => $code,
                'type' => $type,
                'color' => $color,
                'parent_id' => $divisionId,
            ]);

            // Head
            $head = $this->makeUser(
                "رئيس قسم {$name}",
                strtolower($code) . '.head@' . strtolower($company->code) . '.test',
                User::ROLE_DEPARTMENT_HEAD,
                $company->id,
                $dept->id
            );
            $dept->update(['head_id' => $head->id]);

            // Technicians
            $techs = [];
            $techCount = $full ? 2 : 1;
            for ($t = 1; $t <= $techCount; $t++) {
                $techs[] = $this->makeUser(
                    "فني {$name} {$t}",
                    strtolower($code) . ".tech{$t}@" . strtolower($company->code) . '.test',
                    User::ROLE_TECHNICIAN,
                    $company->id,
                    $dept->id
                );
            }

            $departments[] = ['dept' => $dept, 'head' => $head, 'techs' => $techs];
        }

        // Requesters (end users) — each linked to a default location.
        $requesters = [];
        $reqCount = $full ? 3 : 1;
        for ($r = 1; $r <= $reqCount; $r++) {
            $user = $this->makeUser(
                "موظف {$r}",
                "user{$r}@" . strtolower($company->code) . '.test',
                User::ROLE_REQUESTER,
                $company->id
            );
            $user->update(['location_id' => $locations[array_rand($locations)]]);
            $requesters[] = $user;
        }

        // Inventory / assets (for completeness)
        $this->seedSpareAndAssets($company, $departments, $locations[0]);

        // Inventory items, stock movements, purchase requests & orders
        $this->seedInventoryAndPurchasing($company, $admin, $departments[0]['dept']);

        // Tickets across the lifecycle
        $this->seedTickets($company, $departments, $requesters, $locations);

        // Spare-parts requests tied to tickets (full company only)
        if ($full) {
            $this->seedPartRequests($company, $departments);
            $this->seedPurchaseRequests($company, $departments);
        }
    }

    protected function seedPartRequests(Company $company, array $departments): void
    {
        $svc = app(PartRequestWorkflowService::class);
        $warehouse = User::where('company_id', $company->id)->role(User::ROLE_WAREHOUSE_MANAGER)->first();

        // dept 0 => issued, dept 1 => approved (awaiting issue), dept 2 => pending
        foreach (array_slice($departments, 0, 3) as $i => $d) {
            $dept = $d['dept'];
            $head = $d['head'];
            $tech = $d['techs'][0];

            $ticket = Ticket::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('department_id', $dept->id)
                ->where('assigned_to', $tech->id)
                ->whereIn('status', [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_ACCEPTED, Ticket::STATUS_PAUSED])
                ->first();
            if (! $ticket) {
                continue;
            }

            $parts = SparePart::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->forDepartment($dept->id)
                ->take(2)->get();
            if ($parts->isEmpty()) {
                continue;
            }

            $lines = $parts->map(fn ($p) => ['spare_part_id' => $p->id, 'quantity' => rand(1, 3)])->all();
            $pr = $svc->create($ticket, $tech, $lines, 'مطلوب لإكمال إصلاح العطل.');

            if ($i === 0) {
                $svc->approve($pr->fresh('items'), $head);
                if ($warehouse) {
                    $svc->issue($pr->fresh('items'), $warehouse);
                }
            } elseif ($i === 1) {
                $svc->approve($pr->fresh('items'), $head);
            }
            // i === 2 stays pending
        }

        // A custom (out-of-catalogue) request: on head approval it auto-routes to a purchase request.
        if (count($departments) >= 4) {
            $d = $departments[3];
            $ticket = Ticket::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('department_id', $d['dept']->id)
                ->where('assigned_to', $d['techs'][0]->id)
                ->whereIn('status', [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_ACCEPTED, Ticket::STATUS_PAUSED])
                ->first();
            if ($ticket) {
                $pr = $svc->create($ticket, $d['techs'][0], [
                    ['custom_name' => 'محرك مروحة خاص 1.5 حصان', 'quantity' => 1],
                ], 'قطعة غير موجودة بالكتالوج ومطلوبة عاجلًا.');
                $svc->approve($pr->fresh('items'), $d['head']); // ← auto-creates a purchase request
            }
        }
    }

    protected function seedSpareAndAssets(Company $company, array $departments, int $locationId): void
    {
        $partSeq = 1;
        $makeParts = function (int $companyId, int $catId, array $names) use (&$partSeq, $company) {
            foreach ($names as $name) {
                SparePart::create([
                    'company_id' => $companyId,
                    'category_id' => $catId,
                    'name' => $name,
                    'part_number' => 'SP-' . str_pad((string) $partSeq, 3, '0', STR_PAD_LEFT) . '-' . $company->code,
                    'quantity' => rand(4, 40),
                    'min_stock' => rand(3, 8),
                    'unit_price' => rand(20, 300),
                ]);
                $partSeq++;
            }
        };

        // Shared/global category (visible to every department).
        $global = SpareCategory::create(['company_id' => $company->id, 'department_id' => null, 'name' => 'مستهلكات عامة', 'code' => 'GEN']);
        $makeParts($company->id, $global->id, ['شريط لاصق عازل', 'برغي تثبيت', 'قفازات عمل']);

        // Department-specific categories + parts.
        $byType = [
            'it' => ['اسم' => 'قطع تقنية', 'كود' => 'IT', 'قطع' => ['خرطوشة حبر', 'كابل شبكة CAT6', 'مزود طاقة']],
            'maintenance' => ['اسم' => 'قطع صيانة عامة', 'كود' => 'MTN', 'قطع' => ['فلتر مكيف', 'حشوة مطاطية', 'صمام مياه']],
            'mechanical' => ['اسم' => 'قطع ميكانيكية', 'كود' => 'MEC', 'قطع' => ['سير محرك', 'رمان بلي', 'زيت تشحيم']],
            'electrical' => ['اسم' => 'قطع كهربائية', 'كود' => 'ELC', 'قطع' => ['قاطع كهربائي', 'لمبة LED', 'كونتاكتور']],
        ];
        foreach ($departments as $d) {
            $dept = $d['dept'];
            $def = $byType[$dept->type] ?? ['اسم' => 'قطع ' . $dept->name, 'كود' => $dept->code, 'قطع' => ['قطعة غيار 1', 'قطعة غيار 2']];
            $cat = SpareCategory::create([
                'company_id' => $company->id,
                'department_id' => $dept->id,
                'name' => $def['اسم'],
                'code' => $def['كود'],
            ]);
            $makeParts($company->id, $cat->id, $def['قطع']);
        }

        $assetCat = AssetCategory::create(['company_id' => $company->id, 'name' => 'أجهزة', 'code' => 'DEV']);
        foreach ([['مكيف سبليت', 'AST-001'], ['طابعة ليزر', 'AST-002'], ['جهاز كمبيوتر', 'AST-003']] as [$name, $ac]) {
            Asset::create([
                'company_id' => $company->id,
                'category_id' => $assetCat->id,
                'department_id' => $departments[0]['dept']->id,
                'location_id' => $locationId,
                'name' => $name,
                'asset_code' => $ac . '-' . $company->code,
                'status' => 'operational',
            ]);
        }
    }

    protected function seedInventoryAndPurchasing(Company $company, User $admin, Department $dept): void
    {
        /* ---- General inventory: categories + items ---- */
        $catDefs = [
            ['مواد كهربائية', 'ELC', ['قاطع كهربائي', 'سلك نحاس', 'مفتاح إضاءة']],
            ['أدوات يدوية', 'TLS', ['مفك براغي', 'مفتاح إنجليزي', 'مطرقة']],
            ['مستلزمات سباكة', 'PLM', ['صنبور مياه', 'وصلة PVC', 'حشوة مطاطية']],
            ['مواد استهلاكية', 'CON', ['شريط لاصق', 'مادة لاصقة', 'قفازات']],
        ];
        $units = ['قطعة', 'متر', 'علبة', 'لفة'];
        $i = 1;
        foreach ($catDefs as [$catName, $catCode, $items]) {
            $category = Category::create([
                'company_id' => $company->id,
                'name' => $catName,
                'code' => $catCode,
                'status' => 'active',
            ]);
            foreach ($items as $itemName) {
                Item::create([
                    'company_id' => $company->id,
                    'category_id' => $category->id,
                    'name' => $itemName,
                    'code' => 'ITM-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT) . '-' . $company->code,
                    'unit' => $units[array_rand($units)],
                    'location' => 'المستودع الرئيسي - رف ' . chr(64 + rand(1, 6)),
                    'quantity' => rand(5, 120),
                    'price' => rand(10, 500),
                    'status' => 'active',
                ]);
                $i++;
            }
        }

        /* ---- Stock movements on spare parts ---- */
        $spareParts = SparePart::withoutGlobalScopes()->where('company_id', $company->id)->get();
        foreach ($spareParts as $part) {
            // initial stock-in
            StockTransaction::create([
                'company_id' => $company->id,
                'spare_part_id' => $part->id,
                'type' => 'in',
                'quantity' => rand(10, 40),
                'created_by' => $admin->id,
            ]);
            // a consumption movement for some
            if (rand(0, 1)) {
                StockTransaction::create([
                    'company_id' => $company->id,
                    'spare_part_id' => $part->id,
                    'type' => 'out',
                    'quantity' => rand(1, 5),
                    'created_by' => $admin->id,
                ]);
            }
        }

        // Purchase requests are seeded via the real workflow in seedPurchaseRequests().
    }

    /** A department head raises purchase requests that flow through the approval chain. */
    protected function seedPurchaseRequests(Company $company, array $departments): void
    {
        $svc = app(ProcurementService::class);
        $finance = User::where('company_id', $company->id)->role(User::ROLE_FINANCE_MANAGER)->first();

        foreach (array_slice($departments, 0, 2) as $i => $d) {
            $dept = $d['dept'];
            $head = $d['head'];

            $parts = SparePart::withoutGlobalScopes()
                ->where('company_id', $company->id)->forDepartment($dept->id)->take(2)->get();
            if ($parts->isEmpty()) {
                continue;
            }

            $items = $parts->map(fn ($p) => [
                'spare_part_id' => $p->id, 'quantity' => rand(5, 15), 'unit_price' => rand(20, 200),
            ])->all();

            $pr = $svc->createManual($head, [
                'department_id' => $dept->id,
                'fulfillment_type' => PurchaseRequest::TYPE_STOCK,
                'justification' => 'تزويد المخزون بقطع غيار للصيانة الدورية.',
                'supplier' => 'مؤسسة التوريدات الفنية',
            ], $items);
            $svc->submit($pr, $head);

            // Walk the chain for the first one so the demo shows an approved/received PR.
            if ($i === 0) {
                $this->walkPurchaseChain($pr, $svc, $finance);
            }
        }
    }

    /** Drive a purchase request through its remaining approval steps + finance + receive. */
    protected function walkPurchaseChain(PurchaseRequest $pr, ProcurementService $svc, ?User $finance): void
    {
        $guard = 0;
        while ($pr->fresh()->canDeptDecide() && $guard++ < 10) {
            $current = Department::withoutGlobalScopes()->find($pr->fresh()->current_dept_id);
            if (! $current || ! $current->head_id) {
                break;
            }
            $svc->approve($pr->fresh(), User::find($current->head_id));
        }
        if ($pr->fresh()->canFinanceDecide() && $finance) {
            $svc->approve($pr->fresh(), $finance);
        }
        $warehouse = User::where('company_id', $pr->company_id)->role(User::ROLE_WAREHOUSE_MANAGER)->first();
        if ($pr->fresh()->canBeReceived() && $warehouse) {
            $svc->receive($pr->fresh(), $warehouse);
        }
    }

    protected function seedTickets(Company $company, array $departments, array $requesters, array $locations): void
    {
        $priorities = Priority::pluck('id')->all();
        $titles = [
            'عطل في المكيف بالدور الثاني',
            'الطابعة لا تطبع',
            'انقطاع متكرر في الشبكة',
            'تسريب مياه في دورة المياه',
            'مشكلة في الإضاءة',
            'صوت غير طبيعي من المحرك',
            'الجهاز لا يعمل',
            'بطء شديد في الكمبيوتر',
            'باب لا يغلق',
            'مقبس كهرباء معطل',
        ];

        $stages = ['open', 'assigned', 'accepted', 'in_progress', 'paused', 'resolved', 'closed', 'rejected'];
        $i = 0;

        foreach ($departments as $d) {
            $dept = $d['dept'];
            $head = $d['head'];
            $tech = $d['techs'][0];

            // Each department gets a few tickets at different stages.
            $deptStages = count($departments) > 1
                ? ['open', 'assigned', 'in_progress', 'paused', 'resolved', 'closed']
                : $stages;

            foreach ($deptStages as $stage) {
                $requester = $requesters[array_rand($requesters)];

                $ticket = $this->workflow->create([
                    'company_id' => $company->id,
                    'title' => $titles[$i % count($titles)],
                    'description' => 'تم رفع البلاغ من قبل الموظف ويرجى المتابعة في أقرب وقت.',
                    'department_id' => $dept->id,
                    'location_id' => $locations[array_rand($locations)],
                    'priority_id' => $priorities[array_rand($priorities)],
                ], $requester);

                // Backdate creation for nicer reporting spread.
                $ticket->forceFill(['created_at' => now()->subDays(rand(1, 40))])->save();

                $this->advanceToStage($ticket, $stage, $head, $tech);
                $i++;
            }
        }
    }

    protected function advanceToStage($ticket, string $stage, User $head, User $tech): void
    {
        if ($stage === 'open') {
            return;
        }

        $this->workflow->assign($ticket->fresh(), $tech, $head, 'يرجى المباشرة بالحل.');
        if ($stage === 'assigned') {
            return;
        }

        $this->workflow->accept($ticket->fresh(), $tech);
        if ($stage === 'accepted') {
            return;
        }

        $this->workflow->start($ticket->fresh(), $tech);
        $this->workflow->setProgress($ticket->fresh(), $tech, 40);
        if ($stage === 'in_progress') {
            return;
        }

        if ($stage === 'paused') {
            $this->workflow->pause($ticket->fresh(), $tech, 'spare_part', 'بانتظار توفر قطعة الغيار من المستودع.');
            return;
        }

        // resolved / closed / rejected paths
        $this->workflow->setProgress($ticket->fresh(), $tech, 80);

        // Attach a used spare part (demo for the auto-deduction feature).
        $parts = [];
        $part = \App\Models\SparePart::withoutGlobalScopes()
            ->where('company_id', $ticket->company_id)
            ->inRandomOrder()->first();
        if ($part) {
            $parts[] = ['spare_part_id' => $part->id, 'quantity' => rand(1, 3)];
        }

        $this->workflow->resolve($ticket->fresh(), $tech, 'تم استبدال القطعة التالفة واختبار الجهاز بنجاح.', $parts);
        if ($stage === 'resolved') {
            return;
        }

        if ($stage === 'rejected') {
            $this->workflow->reject($ticket->fresh(), $head, 'المشكلة لا تزال قائمة، يرجى إعادة الفحص.');
            return;
        }

        $this->workflow->approve($ticket->fresh(), $head, 'تم التحقق من الإنجاز.');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    protected function makeUser(string $name, string $email, string $role, ?int $companyId, ?int $departmentId = null): User
    {
        $user = User::create([
            'company_id' => $companyId,
            'department_id' => $departmentId,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
