<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spare-parts request workflow tied to tickets.
 * A technician raises a request against a ticket; the department head approves;
 * the warehouse manager issues. Five quantities are tracked per line.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('request_number')->unique();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();

            // pending, approved, rejected, issued, partially_issued, cancelled
            $table->string('status')->default('pending');
            $table->text('note')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();

            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();

            $table->timestamps();
            $table->index(['company_id', 'status']);
            $table->index('ticket_id');
        });

        Schema::create('part_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_request_id')->constrained('part_requests')->cascadeOnDelete();
            // spare_part_id null => قطعة غير موجودة بالكتالوج (custom)؛ custom_name يحمل اسمها
            $table->foreignId('spare_part_id')->nullable()->constrained('spare_parts')->cascadeOnDelete();
            $table->string('custom_name')->nullable();
            $table->integer('qty_requested')->default(1);
            $table->integer('qty_approved')->default(0);
            $table->integer('qty_issued')->default(0);
            $table->integer('qty_used')->default(0);
            $table->integer('qty_returned')->default(0);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->timestamps();
            $table->index('part_request_id');
            $table->index('spare_part_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_request_items');
        Schema::dropIfExists('part_requests');
    }
};
