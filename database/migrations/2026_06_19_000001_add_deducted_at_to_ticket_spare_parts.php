<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Used spare parts are now recorded against a ticket while work is in progress
 * but only drawn from stock when the ticket is approved/closed. `deducted_at`
 * marks the moment stock was taken: NULL = pending (not yet deducted), set =
 * already moved (either at close, or eagerly by a warehouse part-issue).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_spare_parts', function (Blueprint $table) {
            $table->timestamp('deducted_at')->nullable()->after('unit_cost');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_spare_parts', function (Blueprint $table) {
            $table->dropColumn('deducted_at');
        });
    }
};
