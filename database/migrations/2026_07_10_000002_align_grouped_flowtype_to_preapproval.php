<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The staged (parent-grouped) approval flow was briefly initiated with
 * flow_type = 'grouped', but the Pre-Approval Flow UII (showPreApprovalPage /
 * preApprovalFlow.blade) only renders rows with flow_type = 'preapproval'.
 * Align the earlier rows so their approvers show up in that UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('approval_contracts')) {
            return;
        }

        DB::table('approval_contracts')
            ->where('flow_type', 'grouped')
            ->update(['flow_type' => 'preapproval']);
    }

    public function down(): void
    {
        // One-way data alignment; nothing to revert.
    }
};
