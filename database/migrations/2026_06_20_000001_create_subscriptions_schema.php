<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SaaS subscription / trial management for the platform (super-admin).
 *
 *   plans                – purchasable packages (monthly/yearly).
 *   subscription_payments– the billing ledger (online or manual).
 *   companies.*          – the live subscription state per tenant.
 *
 * Existing companies are grandfathered to `active` with no expiry so nothing
 * breaks; new companies start a trial (see Company observer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('SAR');
            $table->string('billing_period')->default('yearly'); // monthly | yearly
            $table->unsignedInteger('duration_days')->default(365);
            $table->unsignedInteger('max_users')->nullable(); // null = unlimited
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('SAR');
            $table->string('status')->default('pending');  // pending | paid | failed | refunded
            $table->string('method')->default('online');   // online | manual
            $table->string('gateway')->nullable();          // stripe | moyasar | tap | ...
            $table->string('reference')->nullable();        // gateway transaction id / receipt no.
            $table->timestamp('paid_at')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });

        Schema::table('companies', function (Blueprint $table) {
            // trial | active | grace | suspended | cancelled
            $table->string('subscription_status')->default('trial')->after('is_active');
            $table->foreignId('plan_id')->nullable()->after('subscription_status')->constrained('plans')->nullOnDelete();
            $table->timestamp('trial_ends_at')->nullable()->after('plan_id');
            $table->timestamp('current_period_end')->nullable()->after('trial_ends_at');
            $table->unsignedInteger('grace_days')->default(3)->after('current_period_end');
        });

        // Grandfather every existing tenant: active, no expiry (never auto-suspended).
        DB::table('companies')->update(['subscription_status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn(['subscription_status', 'trial_ends_at', 'current_period_end', 'grace_days']);
        });
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('plans');
    }
};
