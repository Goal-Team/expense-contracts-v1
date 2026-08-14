<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When a grouped-flow stage is re-entered (e.g. a later stage rejects back to an
 * earlier one, or negotiation is re-sent), createApprovalRows creates a fresh batch
 * of rows with the same stage_name/group_key. The old batch's rows (some already
 * approved/rejected) must be excluded from the stage-completion check, otherwise a
 * prior rejected row keeps the stage from ever completing. This flag marks the old
 * batch as superseded; the completion/activation logic ignores superseded=1 rows,
 * while the approval history still shows them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('approval_contracts')) {
            return;
        }
        if (!Schema::hasColumn('approval_contracts', 'superseded')) {
            Schema::table('approval_contracts', function (Blueprint $table) {
                $table->tinyInteger('superseded')->default(0)->after('flag');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('approval_contracts')) {
            return;
        }
        if (Schema::hasColumn('approval_contracts', 'superseded')) {
            Schema::table('approval_contracts', function (Blueprint $table) {
                $table->dropColumn('superseded');
            });
        }
    }
};
