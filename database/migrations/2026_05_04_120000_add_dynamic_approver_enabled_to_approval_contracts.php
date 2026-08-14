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
            if (!Schema::hasColumn('approval_contracts', 'dynamic_approver_enabled')) {
                $table->tinyInteger('dynamic_approver_enabled')->default(0)->after('awaiting_owner_trigger');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('approval_contracts')) {
            return;
        }

        Schema::table('approval_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('approval_contracts', 'dynamic_approver_enabled')) {
                $table->dropColumn('dynamic_approver_enabled');
            }
        });
    }
};
