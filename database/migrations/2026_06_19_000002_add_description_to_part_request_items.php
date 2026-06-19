<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Non-catalog (custom) part requests carry a free-text description in addition
 * to the name, so the warehouse/buyer knows exactly what to source.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('part_request_items', function (Blueprint $table) {
            $table->text('description')->nullable()->after('custom_name');
        });
    }

    public function down(): void
    {
        Schema::table('part_request_items', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
