<?php

// Applied on the local dev database 2026-08-22 under the dev's standing approval of the same day:
// migrations get applied locally and reported after, so index work does not wait. Production is
// still the dev's to run.
//
// Why: viewContract runs this on every detail page view (ContractController.php:780):
//
//   SELECT GROUP_CONCAT(lv SEPARATOR ',') FROM (
//     SELECT @pv:=(SELECT GROUP_CONCAT(id SEPARATOR ',') FROM contracts
//       WHERE FIND_IN_SET(parentcontract, @pv)) AS lv FROM contracts
//     JOIN (SELECT @pv:=<id>) tmp) a
//
// The inner subquery reads the whole contracts table once for every row of the outer one:
// 3,018 x 3,018 row reads. It needs two columns, id and parentcontract, but it scans the
// clustered index, which is the full 111-column row. Measured 2026-08-21:
//
//   same 3,018 (id, parentcontract) pairs in a two-column temp table :   3 s
//   the real contracts table                                          : over 120 s, then the
//                                                                       IIS FastCGI timeout
//                                                                       returns HTTP 500
//
// contracts is 110 MB of data for 27 MB of content, because ROW_FORMAT=Dynamic pushes the
// long encrypted text columns off-page and each off-page value costs a whole 16 KB page. The
// local innodb_buffer_pool_size is 16 MB, so every one of the 3,018 scans reads from disk.
//
// This index covers both columns the subquery needs, so the scan reads about 50 KB of index
// instead of 110 MB of rows. FIND_IN_SET cannot use an index for lookup - the win here is the
// covering scan, nothing else.
//
// This is a smaller fix than the query deserves. The query is quadratic and stays quadratic.
// Replacing it with a WITH RECURSIVE walk (MariaDB 10.2+, this server is 10.4.24) is the real
// answer, and that is page code, not schema.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->index(['parentcontract', 'id'], 'contracts_parentcontract_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex('contracts_parentcontract_id_index');
        });
    }
};
