<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('contracts')) {
            return;
        }

        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'preapprover_payload')) {
                $table->longText('preapprover_payload')->nullable()->after('rules_id');
            }

            if (!Schema::hasColumn('contracts', 'approval_gate_state')) {
                $table->string('approval_gate_state')->nullable()->after('preapprover_payload');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('contracts')) {
            return;
        }

        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'approval_gate_state')) {
                $table->dropColumn('approval_gate_state');
            }

            if (Schema::hasColumn('contracts', 'preapprover_payload')) {
                $table->dropColumn('preapprover_payload');
            }
        });
    }
};
