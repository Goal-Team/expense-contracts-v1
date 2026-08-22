<?php

// Applied on the local dev database 2026-08-22 under the dev's standing approval of the same day:
// migrations get applied locally and reported after. Production is still the dev's to run.
//
// Why: the contract detail page runs this once on every load
// (ContractController::viewContract, line 656). It fills the History tab:
//
//   select * from contracts_history where id = ? order by created_at desc
//
// `id` is the contract id. It is NOT the primary key of this table - the primary key is
// `history_id` - so the filter had no index and read every row. `created_at` sits in the same
// index so the sort is free as well.
//
// HONEST NOTE ON THE MEASUREMENT. This table holds 17 rows on the seeded local set, so the
// index moves no number here and none is claimed. It is written for the shape of the table,
// not for its size today: contracts_history grows by one row every time any user saves any
// contract, so on a client database it is the one table on this page with no ceiling. See
// .scratch/contract-detail-page-perf/measurements/report.md.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts_history', function (Blueprint $table) {
            $table->index(['id', 'created_at'], 'contracts_history_id_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('contracts_history', function (Blueprint $table) {
            $table->dropIndex('contracts_history_id_created_at_index');
        });
    }
};
