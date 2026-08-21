<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Narrow approval_contracts.approval_status from varchar(1000) to varchar(20) and index it.
 *
 * See .scratch/contracts-dashboard-perf/issues/17-plain-columns-experiment.md
 *
 * Must run AFTER `php artisan contract:convert-approval-status --apply`. The column holds
 * 200-character ciphertext until that has run, and narrowing first would cut every value.
 * up() refuses to run if it finds ciphertext still there.
 *
 * Why narrowing is not optional: the index is the whole point, and varchar(1000) in utf8mb4 is
 * 4,000 bytes, far past what InnoDB will index. At 20 it is 80 bytes and the four-column index
 * below fits comfortably.
 *
 * Why MODIFY COLUMN and not $table->string(...)->change(): ->change() needs doctrine/dbal,
 * which this project does not have installed. The ALTER is written out here, inside a
 * reviewed migration, rather than run by hand in a client - which is what the CLAUDE.md rule
 * is protecting against.
 *
 * It names no charset and no collation, by the rule ticket 20 settled: the collation depends on
 * the client's database server and a migration cannot know it. A MODIFY COLUMN that omits both
 * inherits the table default, which is right on every server.
 *
 * The index leads on approval_status because that is the selective part. The counter asks for
 * row_status = 1 AND superseded = 0 AND approval_status = 'pending', and on the dev database
 * that is 2,129 rows out of 13,867 - about 15% - while row_status and superseded are true for
 * nearly every row. contract_id sits on the end so the join to contracts is served from the
 * index too.
 *
 * The existing idx_approval_contracts_contract_id stays. It serves the other direction, where a
 * query starts from one contract.
 */
return new class extends Migration
{
    private const TABLE = 'approval_contracts';

    private const INDEX = 'idx_approval_contracts_status_lookup';

    public function up(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        $ciphertext = DB::table(self::TABLE)
            ->whereRaw("LEFT(approval_status, 2) = 'ey'")
            ->count();

        if ($ciphertext > 0) {
            throw new RuntimeException(
                "approval_status still holds {$ciphertext} encrypted value(s). Narrowing the column "
                . 'now would cut every one of them. Run "php artisan contract:convert-approval-status '
                . '--apply" first.'
            );
        }

        DB::statement('ALTER TABLE `' . self::TABLE . '` MODIFY `approval_status` varchar(20) NULL');

        if (!$this->hasIndex(self::INDEX)) {
            DB::statement(
                'ALTER TABLE `' . self::TABLE . '` ADD INDEX `' . self::INDEX . '`'
                . ' (`approval_status`, `row_status`, `superseded`, `contract_id`)'
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }

        if ($this->hasIndex(self::INDEX)) {
            DB::statement('ALTER TABLE `' . self::TABLE . '` DROP INDEX `' . self::INDEX . '`');
        }

        // Back to what it was, wide enough to hold ciphertext again. Widening alone does not
        // re-encrypt anything - run
        // `php artisan contract:convert-approval-status --apply --down` after this.
        DB::statement('ALTER TABLE `' . self::TABLE . '` MODIFY `approval_status` varchar(1000) NULL');
    }

    /**
     * Asked of information_schema rather than through Schema, so this is safe to re-run on a
     * database where the index already exists, and needs no doctrine/dbal.
     */
    private function hasIndex(string $name): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', self::TABLE)
            ->where('INDEX_NAME', $name)
            ->exists();
    }
};
