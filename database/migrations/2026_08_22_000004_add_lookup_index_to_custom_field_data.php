<?php

// Applied on the local dev database 2026-08-22 under the dev's standing approval of the same day:
// migrations get applied locally and reported after. Production is still the dev's to run.
//
// Why: dataCustomFields() and dataCustomFieldsParty() in app/helpers.php run this query, and the
// contract detail page calls them many times per load - 8 times on the Details tab, up to about
// 48 on the tabs that draw the whole custom-field set:
//
//   select * from custom_field_data
//   where custom_field_group_id = ? and custom_field_id = ? and custom_field_group = ?
//   order by id desc limit 1
//
// The table had no index at all past its primary key, so every one of those calls read the whole
// table. `id` is the last column of the index so the `latest('id')` sort is served too.
//
// Column order is most selective first: custom_field_group_id is the contract id and picks a
// handful of rows, custom_field_id picks one field inside that, and custom_field_group holds
// only two values in the whole application - 'contracts' and 'parties'.
//
// Why raw SQL and not $table->index(): custom_field_group is a TEXT column, so MariaDB demands a
// prefix length and Laravel's Blueprint cannot write one. 20 characters is longer than either
// value the application stores. The table is MyISAM and latin1, unlike the rest of this schema;
// the index does not change that and the migration names no charset, per the rule.
//
// HONEST NOTE ON THE MEASUREMENT. This table holds 6 rows on the seeded local set, so the index
// moves no number here and none is claimed. It is written for the shape of the table: it holds
// one row per custom field value per contract, so it grows with the contract count on a client
// database. See .scratch/contract-detail-page-perf/measurements/report.md.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'CREATE INDEX custom_field_data_group_field_lookup_index
             ON custom_field_data (custom_field_group_id, custom_field_id, custom_field_group(20), id)'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX custom_field_data_group_field_lookup_index ON custom_field_data');
    }
};
