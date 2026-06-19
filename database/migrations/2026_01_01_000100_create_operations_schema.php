<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |----------------------------------------------------------------------
        | DEPARTMENTS (by task type, each with a head)
        |----------------------------------------------------------------------
        */
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type')->default('general'); // it, maintenance, mechanical, electrical, hvac, general...
            // parent_id => الإدارة/القسم الأعلى في التسلسل الإداري (للاعتماد المتصاعد)
            $table->foreignId('parent_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('head_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            // false => إدارة/مستوى إداري للاعتماد فقط، لا يستقبل بلاغات (مثل إدارة التشغيل)
            $table->boolean('accepts_tickets')->default(true);
            $table->timestamps();
            $table->index('company_id');
        });

        // Wire users.department_id now that departments exists
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });

        /*
        |----------------------------------------------------------------------
        | LOCATIONS
        |----------------------------------------------------------------------
        */
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['building', 'floor', 'room', 'area'])->default('building');
            $table->foreignId('parent_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('full_path')->nullable();
            $table->timestamps();
            $table->index('company_id');
        });

        // Wire users.location_id now that locations exists.
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete();
            $table->index('location_id');
        });

        /*
        |----------------------------------------------------------------------
        | PRIORITIES
        |----------------------------------------------------------------------
        */
        Schema::create('priorities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('level')->default(1);
            $table->string('color')->nullable();
            $table->timestamps();
        });

        /*
        |----------------------------------------------------------------------
        | TICKET STATUSES (reference/labels — workflow uses tickets.status enum)
        |----------------------------------------------------------------------
        */
        Schema::create('ticket_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();
        });

        /*
        |----------------------------------------------------------------------
        | ASSET CATEGORIES + ASSETS
        |----------------------------------------------------------------------
        */
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('serial_number')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('status')->default('operational'); // operational, down, maintenance, retired
            $table->date('installation_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->timestamps();
            $table->index('company_id');
        });

        /*
        |----------------------------------------------------------------------
        | INVENTORY: CATEGORIES + ITEMS (simple stock)
        |----------------------------------------------------------------------
        */
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('unit')->nullable();
            $table->string('location')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        /*
        |----------------------------------------------------------------------
        | SPARE PARTS
        |----------------------------------------------------------------------
        */
        Schema::create('spare_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            // department_id null => عام/مشترك لكل الأقسام
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
        });

        Schema::create('spare_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('part_number')->unique();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained('spare_categories')->nullOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('min_stock')->default(0);
            $table->integer('max_stock')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->timestamps();
        });

        /*
        |----------------------------------------------------------------------
        | TICKETS (core work item + full lifecycle)
        |----------------------------------------------------------------------
        */
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('ticket_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('location_detail')->nullable(); // free-text precision (e.g. "خلف المولّد الشرقي")
            $table->foreignId('priority_id')->nullable()->constrained('priorities')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();

            // Lifecycle
            $table->string('status')->default('open'); // open, assigned, accepted, in_progress, paused, resolved, closed, rejected, cancelled
            $table->unsignedTinyInteger('progress')->default(0);

            // People
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // requester
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // technician
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete(); // head
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();   // head approving

            // Timestamps for SLA / tracking
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('due_at')->nullable();

            $table->text('resolution_note')->nullable();
            $table->text('rejected_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index('status');
            $table->index('department_id');
            $table->index('assigned_to');
            $table->index('created_by');
        });

        /*
        |----------------------------------------------------------------------
        | TICKET EVENTS (timeline / full status tracking)
        |----------------------------------------------------------------------
        */
        Schema::create('ticket_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // created, assigned, accepted, started, paused, resumed, progress, resolved, approved, rejected, commented, reopened
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index('ticket_id');
        });

        /*
        |----------------------------------------------------------------------
        | TICKET PAUSE LOGS (reason for pause, e.g. spare part issue)
        |----------------------------------------------------------------------
        */
        Schema::create('ticket_pause_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('paused_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason_code')->nullable(); // spare_part, awaiting_approval, external, other
            $table->text('reason')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->foreignId('resumed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resumed_at')->nullable();
            $table->timestamps();
            $table->index('ticket_id');
        });

        /*
        |----------------------------------------------------------------------
        | TICKET COMMENTS (follow-up thread)
        |----------------------------------------------------------------------
        */
        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
            $table->index('ticket_id');
        });

        /*
        |----------------------------------------------------------------------
        | TICKET ATTACHMENTS
        |----------------------------------------------------------------------
        */
        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });

        /*
        |----------------------------------------------------------------------
        | TICKET SPARE PARTS (parts used to resolve a ticket)
        |----------------------------------------------------------------------
        */
        Schema::create('ticket_spare_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            // Nullable: a used part may be out-of-catalogue (custom), recorded by name only.
            $table->foreignId('spare_part_id')->nullable()->constrained('spare_parts')->cascadeOnDelete();
            $table->string('custom_name')->nullable(); // name for an out-of-catalogue used part
            $table->integer('quantity_used')->default(1);
            $table->decimal('unit_cost', 10, 2)->nullable(); // snapshot of cost at time of consumption
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        /*
        |----------------------------------------------------------------------
        | PURCHASE REQUESTS / ORDERS
        |----------------------------------------------------------------------
        */
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('request_number')->unique();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            // Procurement raised from a parts shortage links back to the ticket + part request.
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->unsignedBigInteger('part_request_id')->nullable();
            // draft, pending_dept, pending_finance, approved, rejected, received
            $table->string('status')->default('draft');
            // stock = توريد للمخزون ، direct = شراء مباشر عاجل لا يدخل المستودع
            $table->string('fulfillment_type')->default('stock');
            // القسم الذي ينتظر اعتماد رئيسه الآن أثناء تصاعد السلسلة
            $table->unsignedBigInteger('current_dept_id')->nullable();
            $table->text('justification')->nullable();
            $table->string('supplier')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->index('part_request_id');
            $table->index(['company_id', 'status']);
        });

        // Audit trail of each approval step (department levels + finance) for the printable form.
        Schema::create('purchase_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('stage'); // dept | finance
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision'); // approved | rejected | auto
            $table->text('note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->index('purchase_request_id');
        });

        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->foreignId('spare_part_id')->nullable()->constrained('spare_parts')->nullOnDelete();
            $table->string('custom_name')->nullable();
            $table->unsignedBigInteger('part_request_item_id')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('po_number')->unique();
            $table->foreignId('purchase_request_id')->nullable()->constrained('purchase_requests')->nullOnDelete();
            $table->string('supplier')->nullable();
            $table->enum('status', ['open', 'received', 'closed'])->default('open');
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('spare_part_id')->constrained('spare_parts')->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->timestamps();
        });

        /*
        |----------------------------------------------------------------------
        | STOCK TRANSACTIONS
        |----------------------------------------------------------------------
        */
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->foreignId('spare_part_id')->constrained('spare_parts')->cascadeOnDelete();
            $table->enum('type', ['in', 'out']);
            $table->integer('quantity');
            $table->foreignId('related_ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->foreignId('related_purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        /*
        |----------------------------------------------------------------------
        | AUDIT LOGS
        |----------------------------------------------------------------------
        */
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['location_id']);
        });

        foreach ([
            'audit_logs', 'stock_transactions', 'purchase_order_items', 'purchase_orders',
            'purchase_request_items', 'purchase_requests', 'ticket_spare_parts', 'ticket_attachments',
            'ticket_comments', 'ticket_pause_logs', 'ticket_events', 'tickets', 'spare_parts',
            'spare_categories', 'items', 'categories', 'assets', 'asset_categories', 'ticket_statuses',
            'priorities', 'locations', 'departments',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
