<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('contract_health_checks', 'overhead_allocation')) {
            Schema::table('contract_health_checks', function (Blueprint $table) {
                $table->decimal('overhead_allocation', 12, 2)->nullable()->default(0)->after('consultation_prices');
                $table->decimal('approved_cost', 12, 2)->nullable()->after('overhead_allocation');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('contract_health_checks', 'overhead_allocation')) {
            Schema::table('contract_health_checks', function (Blueprint $table) {
                $table->dropColumn(['overhead_allocation', 'approved_cost']);
            });
        }
    }
};