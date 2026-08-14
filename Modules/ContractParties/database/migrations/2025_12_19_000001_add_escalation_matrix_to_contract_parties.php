<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('contract_parties', 'escalation_matrix')) {
            Schema::table('contract_parties', function (Blueprint $table) {
                $table->json('escalation_matrix')->nullable()->after('approvers');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('contract_parties', 'escalation_matrix')) {
            Schema::table('contract_parties', function (Blueprint $table) {
                $table->dropColumn('escalation_matrix');
            });
        }
    }
};
