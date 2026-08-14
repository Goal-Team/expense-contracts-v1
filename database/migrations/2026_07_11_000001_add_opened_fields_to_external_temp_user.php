<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ExternalTempUser::$fillable declares opened / opened_date / ip_details and the
 * external-access flows (negotiationRespond, external approval) write to them, but
 * the columns were never added to the table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('external_temp_user')) {
            return;
        }

        Schema::table('external_temp_user', function (Blueprint $table) {
            if (!Schema::hasColumn('external_temp_user', 'opened')) {
                $table->tinyInteger('opened')->default(0)->after('is_active');
            }
            if (!Schema::hasColumn('external_temp_user', 'opened_date')) {
                $table->dateTime('opened_date')->nullable()->after('opened');
            }
            if (!Schema::hasColumn('external_temp_user', 'ip_details')) {
                $table->text('ip_details')->nullable()->after('opened_date');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('external_temp_user')) {
            return;
        }

        Schema::table('external_temp_user', function (Blueprint $table) {
            foreach (['opened', 'opened_date', 'ip_details'] as $col) {
                if (Schema::hasColumn('external_temp_user', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
