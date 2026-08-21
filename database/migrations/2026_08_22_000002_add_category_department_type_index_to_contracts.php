<?php

// Applied on the local dev database 2026-08-22 under the dev's standing approval of the same day:
// migrations get applied locally and reported after, so index work does not wait. Production is
// still the dev's to run.
//
// Why: the Details tab of the contract detail page runs this once on every load
// (ContractController::relatedContractLists(), the $contractsoldothers query). It feeds the
// "Category Previous Contracts" table:
//
//   select * from contracts
//   where catgoery_id = ? and department_id = ? and contract_type = ?
//     and not id = ?
//
// No index covered those three columns, so MariaDB read all 3,018 rows of a 110 MB table
// against a 16 MB buffer pool. Measured 898-1,823 ms, about 24% of the Details tab, to return
// a handful of rows: 2,633 of the 3,018 rows sit in a group of their own and the biggest group
// holds 12.
//
// Column order is most selective first, measured on the seeded set:
//
//   contract_type  73 values, about   41 rows each
//   department_id  36 values, about   84 rows each
//   catgoery_id     3 values, about 1006 rows each
//
// All three tests are equality, so this query works with any order. The order matters for the
// next query that uses only part of the index, and a leading catgoery_id would give it a
// third of the table.
//
// The index is not covering - the query still reads the matching rows - but it takes the read
// from 3,018 rows to about one.
//
// Why raw SQL and not $table->index(): contract_type and catgoery_id are TEXT columns, so
// MariaDB demands a prefix length and refuses the whole column - "Specified key was too long;
// max key length is 3072 bytes". Laravel's Blueprint cannot write a prefix length. 20
// characters holds a 20-digit id, and the longest value in the table today is 2 characters.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'CREATE INDEX contracts_type_department_category_index
             ON contracts (contract_type(20), department_id, catgoery_id(20))'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX contracts_type_department_category_index ON contracts');
    }
};
