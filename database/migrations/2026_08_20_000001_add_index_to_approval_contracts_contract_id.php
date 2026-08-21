<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index approval_contracts.contract_id.
     *
     * The dashboard's approvals query joins this table on contract_id on every load.
     * Today the table has PRIMARY only, so that join reads the whole table
     * (EXPLAIN: type=ALL). This index stops it getting slower as rows pile up.
     * InnoDB builds it online - no table lock.
     */
    public function up(): void
    {
        Schema::table('approval_contracts', function (Blueprint $table) {
            $table->index('contract_id', 'idx_approval_contracts_contract_id');
        });
    }

    public function down(): void
    {
        Schema::table('approval_contracts', function (Blueprint $table) {
            $table->dropIndex('idx_approval_contracts_contract_id');
        });
    }
};
