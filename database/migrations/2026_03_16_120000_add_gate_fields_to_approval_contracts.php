<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('approval_contracts')) {
            return;
        }

        Schema::table('approval_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('approval_contracts', 'group_key')) {
                $table->string('group_key')->nullable()->after('unique_id');
            }

            if (!Schema::hasColumn('approval_contracts', 'stage_type')) {
                $table->string('stage_type')->nullable()->after('group_key');
            }

            if (!Schema::hasColumn('approval_contracts', 'stage_origin')) {
                $table->string('stage_origin')->nullable()->after('stage_type');
            }

            if (!Schema::hasColumn('approval_contracts', 'auto_next_enabled')) {
                $table->tinyInteger('auto_next_enabled')->default(0)->after('stage_origin');
            }

            if (!Schema::hasColumn('approval_contracts', 'awaiting_owner_trigger')) {
                $table->tinyInteger('awaiting_owner_trigger')->default(0)->after('auto_next_enabled');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('approval_contracts')) {
            return;
        }

        Schema::table('approval_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('approval_contracts', 'awaiting_owner_trigger')) {
                $table->dropColumn('awaiting_owner_trigger');
            }

            if (Schema::hasColumn('approval_contracts', 'auto_next_enabled')) {
                $table->dropColumn('auto_next_enabled');
            }

            if (Schema::hasColumn('approval_contracts', 'stage_origin')) {
                $table->dropColumn('stage_origin');
            }

            if (Schema::hasColumn('approval_contracts', 'stage_type')) {
                $table->dropColumn('stage_type');
            }

            if (Schema::hasColumn('approval_contracts', 'group_key')) {
                $table->dropColumn('group_key');
            }
        });
    }
};
