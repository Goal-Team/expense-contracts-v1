<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Convert contract_party_data from MyISAM/latin1 with TEXT join columns to
 * InnoDB/utf8mb4 with varchar join columns, then index them.
 *
 * NOT a migration, by the dev's call 2026-08-20: the collation name depends on the
 * client's database type and version, so it is passed in rather than written down.
 * See .scratch/contracts-dashboard-perf/issues/20-migration-portability.md
 *
 *   php artisan contract:convert-party-data                      # checks only, changes nothing
 *   php artisan contract:convert-party-data --apply              # utf8mb4_unicode_ci
 *   php artisan contract:convert-party-data --apply --collation=utf8mb4_general_ci
 *
 * It refuses to do anything until every check passes:
 *
 *  - the collation exists on this server
 *  - the collation is case-INSENSITIVE, proved by asking the server whether
 *    'a' = 'A' under it, not by reading the name. Two queries compare
 *    contract_party_type against lowercase 'internal'
 *    (ContractController.php:725 and :4358) and only work because case is ignored
 *  - it matches what `contracts` uses, or the dev says otherwise with --force-mismatch
 *  - no existing value is longer than the target width, so nothing is ever cut
 *
 * Reversing is not risk-free - latin1 cannot hold every character utf8mb4 can - so
 * there is no --down. Restore a backup. The SQL to go back by hand is written in
 * database/manual/001-contract-party-data-innodb-utf8mb4.sql.
 */
class ConvertPartyDataCollation extends Command
{
    protected $signature = 'contract:convert-party-data
        {--collation=utf8mb4_unicode_ci : collation to convert to; must be case-insensitive}
        {--width=32 : varchar width for the two join columns}
        {--apply : actually run it; without this the command only checks}
        {--force-mismatch : run even if the collation differs from the contracts table}';

    protected $description = 'Convert contract_party_data to InnoDB + a given collation and index its join columns';

    /**
     * The two columns whose type changes. Deliberately a short, explicit list and not
     * "every text column": party_address is a free-form address and stays TEXT.
     */
    private const JOIN_COLUMNS = ['contract_party_type', 'contract_party_location_id'];

    private const TABLE = 'contract_party_data';

    /**
     * Databases this command must never change, whatever it is pointed at.
     * goalapp_apollo and the other tenant databases are read-only for this project
     * (CLAUDE.md).
     */
    private const NEVER = ['goalapp_apollo', 'mysql', 'information_schema', 'performance_schema', 'sys'];

    public function handle(): int
    {
        $collation = trim((string) $this->option('collation'));
        $width = (int) $this->option('width');
        $apply = (bool) $this->option('apply');

        $database = DB::getDatabaseName();

        $this->line('');
        $this->line('  database  ' . $database);
        $this->line('  table     ' . self::TABLE);
        $this->line('  collation ' . $collation);
        $this->line('  width     varchar(' . $width . ')');
        $this->line('  mode      ' . ($apply ? 'APPLY' : 'check only'));
        $this->line('');

        if (in_array($database, self::NEVER, true)) {
            $this->error($database . ' is never changed by this project. Nothing done.');

            return self::FAILURE;
        }

        if ($width < 1 || $width > 191) {
            $this->error('--width must be between 1 and 191.');

            return self::FAILURE;
        }

        $charset = $this->charsetFor($collation);

        if ($charset === null) {
            $this->error('Collation ' . $collation . ' does not exist on this server.');
            $this->line('  Case-insensitive utf8mb4 collations that do exist here:');

            foreach ($this->offeredCollations() as $name) {
                $this->line('    ' . $name);
            }

            return self::FAILURE;
        }

        $this->info('Collation exists, character set is ' . $charset . '.');

        if (!$this->isCaseInsensitive($collation)) {
            $this->error('Collation ' . $collation . ' is case-SENSITIVE.');
            $this->line('  Two queries compare contract_party_type against lowercase');
            $this->line('  "internal" and would silently match nothing. Pick a _ci collation.');

            return self::FAILURE;
        }

        $this->info('Collation ignores case, as it must.');

        if (!$this->checkMatchesContractsTable($collation)) {
            return self::FAILURE;
        }

        if (!$this->checkNothingIsTooLong($width)) {
            return self::FAILURE;
        }

        $rowsBefore = DB::table(self::TABLE)->count();
        $this->info($rowsBefore . ' rows before.');

        if (!$apply) {
            $this->line('');
            $this->line('  Every check passed. Nothing was changed.');
            $this->line('  Add --apply to run it. Take a backup first: this rebuilds the');
            $this->line('  table and locks it while it runs.');
            $this->line('');

            return self::SUCCESS;
        }

        return $this->applyChange($database, $charset, $collation, $width, $rowsBefore);
    }

    /**
     * The four steps, in this order because each needs the one before: InnoDB before
     * the indexes, the character set before the column types, varchar before an index
     * that would otherwise need a prefix length.
     */
    private function applyChange(string $database, string $charset, string $collation, int $width, int $rowsBefore): int
    {
        Log::info('convert-party-data starting', [
            'database' => $database,
            'collation' => $collation,
            'width' => $width,
            'rows' => $rowsBefore,
        ]);

        $table = self::TABLE;

        try {
            $this->line('  1/4 engine -> InnoDB');
            DB::statement('ALTER TABLE `' . $table . '` ENGINE = InnoDB');

            $this->line('  2/4 character set -> ' . $charset . ' / ' . $collation);
            DB::statement(
                'ALTER TABLE `' . $table . '` CONVERT TO CHARACTER SET '
                . $charset . ' COLLATE ' . $collation
            );

            $this->line('  3/4 join columns -> varchar(' . $width . ')');
            $modifies = [];

            foreach (self::JOIN_COLUMNS as $column) {
                $modifies[] = 'MODIFY `' . $column . '` varchar(' . $width . ') '
                    . 'CHARACTER SET ' . $charset . ' COLLATE ' . $collation . ' NULL';
            }

            DB::statement('ALTER TABLE `' . $table . '` ' . implode(', ', $modifies));

            $this->line('  4/4 indexes');
            $this->addIndexes($table);
        } catch (\Throwable $e) {
            // No row data in the log - this table holds party addresses.
            Log::error('convert-party-data failed', [
                'database' => $database,
                'message' => $e->getMessage(),
            ]);

            $this->error('Failed: ' . $e->getMessage());
            $this->line('  The table may be part converted. Check it before running again.');

            return self::FAILURE;
        }

        return $this->verify($database, $rowsBefore);
    }

    /**
     * Add the two indexes, skipping either one that is already there, so the command
     * can be run again after a part failure without erroring on the index it made.
     */
    private function addIndexes(string $table): void
    {
        $wanted = [
            'idx_cpd_custom_field_group_id' => ['custom_field_group_id'],
            'idx_cpd_type_location' => self::JOIN_COLUMNS,
        ];

        $existing = collect(DB::select('SHOW INDEX FROM `' . $table . '`'))
            ->pluck('Key_name')
            ->unique()
            ->all();

        foreach ($wanted as $name => $columns) {
            if (in_array($name, $existing, true)) {
                $this->line('      ' . $name . ' already there, skipped');

                continue;
            }

            $list = '`' . implode('`, `', $columns) . '`';
            DB::statement('ALTER TABLE `' . $table . '` ADD INDEX `' . $name . '` (' . $list . ')');
            $this->line('      ' . $name . ' added');
        }
    }

    /**
     * Prove it landed rather than assuming it did. Row count is the one that matters:
     * a rebuild that lost rows is the worst outcome and the quietest.
     */
    private function verify(string $database, int $rowsBefore): int
    {
        $table = self::TABLE;

        $after = DB::selectOne(
            'SELECT engine, table_collation FROM information_schema.tables
              WHERE table_schema = ? AND table_name = ?',
            [$database, $table]
        );

        $rowsAfter = DB::table($table)->count();

        $columns = DB::select(
            'SELECT column_name, column_type FROM information_schema.columns
              WHERE table_schema = ? AND table_name = ? AND column_name IN (?, ?)',
            array_merge([$database, $table], self::JOIN_COLUMNS)
        );

        $indexes = collect(DB::select('SHOW INDEX FROM `' . $table . '`'))
            ->pluck('Key_name')
            ->unique()
            ->all();

        $this->line('');
        $this->info('Done. What the table looks like now:');
        $this->line('  engine     ' . ($after->engine ?? $after->ENGINE));
        $this->line('  collation  ' . ($after->table_collation ?? $after->TABLE_COLLATION));

        foreach ($columns as $column) {
            $name = $column->column_name ?? $column->COLUMN_NAME;
            $type = $column->column_type ?? $column->COLUMN_TYPE;
            $this->line('  ' . $name . '  ' . $type);
        }

        $this->line('  indexes    ' . implode(', ', $indexes));
        $this->line('  rows       ' . $rowsAfter . ' (was ' . $rowsBefore . ')');
        $this->line('');

        Log::info('convert-party-data finished', [
            'database' => $database,
            'rows_before' => $rowsBefore,
            'rows_after' => $rowsAfter,
        ]);

        if ($rowsAfter !== $rowsBefore) {
            $this->error('Row count changed: ' . $rowsBefore . ' before, ' . $rowsAfter . ' after. Restore the backup.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * The character set the given collation belongs to, or null if the server has no
     * such collation. Reading it rather than assuming utf8mb4 means a latin1 or
     * utf8mb3 collation still produces a coherent ALTER rather than a broken one.
     */
    private function charsetFor(string $collation): ?string
    {
        $row = DB::selectOne(
            'SELECT character_set_name FROM information_schema.collations WHERE collation_name = ?',
            [$collation]
        );

        if ($row === null) {
            return null;
        }

        return $row->character_set_name ?? $row->CHARACTER_SET_NAME ?? null;
    }

    /**
     * Ask the server, do not read the name. Every collation this app wants ends in
     * _ci, but the name is a convention and the behaviour is the thing that matters.
     *
     * The collation cannot be a bound parameter - COLLATE takes an identifier, not a
     * value - so it is only ever interpolated after charsetFor() has found it in
     * information_schema. A name that is not a real collation never reaches here.
     */
    private function isCaseInsensitive(string $collation): bool
    {
        $row = DB::selectOne("SELECT ('a' = 'A' COLLATE " . $collation . ') AS same');

        return (int) ($row->same ?? 0) === 1;
    }

    /**
     * The whole point of the conversion is that contract_party_data and contracts
     * compare without crossing a collation boundary, so a mismatch here means the
     * change would not achieve what it is for.
     */
    private function checkMatchesContractsTable(string $collation): bool
    {
        $contracts = DB::selectOne(
            'SELECT table_collation FROM information_schema.tables
              WHERE table_schema = ? AND table_name = ?',
            [DB::getDatabaseName(), 'contracts']
        );

        if ($contracts === null) {
            $this->warn('No contracts table here, so there is nothing to match. Carrying on.');

            return true;
        }

        $theirs = $contracts->table_collation ?? $contracts->TABLE_COLLATION;

        if ($theirs === $collation) {
            $this->info('Matches the contracts table (' . $theirs . ').');

            return true;
        }

        $this->error('The contracts table is ' . $theirs . ', not ' . $collation . '.');
        $this->line('  Converting to a different collation leaves a mismatch between the two');
        $this->line('  tables every party lookup joins, which is the problem this is meant to');
        $this->line('  fix. Either pass --collation=' . $theirs . ' or, if you mean it,');
        $this->line('  pass --force-mismatch.');

        return (bool) $this->option('force-mismatch');
    }

    /**
     * Measure before narrowing. MySQL in strict mode would refuse the ALTER anyway,
     * but the error it gives is not a sentence anyone can act on.
     */
    private function checkNothingIsTooLong(int $width): bool
    {
        $selects = [];

        foreach (self::JOIN_COLUMNS as $column) {
            $selects[] = 'MAX(CHAR_LENGTH(`' . $column . '`)) AS `' . $column . '`';
        }

        $max = DB::selectOne('SELECT ' . implode(', ', $selects) . ' FROM `' . self::TABLE . '`');
        $tooLong = [];

        foreach (self::JOIN_COLUMNS as $column) {
            $length = (int) ($max->{$column} ?? 0);
            $this->line('  longest ' . $column . ': ' . $length);

            if ($length > $width) {
                $tooLong[] = $column . ' (' . $length . ')';
            }
        }

        if ($tooLong !== []) {
            $this->error('Longer than ' . $width . ': ' . implode(', ', $tooLong) . '. Nothing done.');
            $this->line('  Raise --width or look at those rows first. Never cut them.');

            return false;
        }

        $this->info('Nothing is longer than ' . $width . ', so nothing gets cut.');

        return true;
    }

    /**
     * What to offer when the collation asked for is not there. Only case-insensitive
     * utf8mb4 ones, because those are the only ones this table should end up with,
     * and only the two that exist on both MySQL 8 and MariaDB 10.4.
     */
    private function offeredCollations(): array
    {
        $rows = DB::select(
            "SELECT collation_name FROM information_schema.collations
              WHERE character_set_name = 'utf8mb4' AND collation_name IN (?, ?)",
            ['utf8mb4_unicode_ci', 'utf8mb4_general_ci']
        );

        return collect($rows)
            ->map(function ($row) {
                return $row->collation_name ?? $row->COLLATION_NAME;
            })
            ->all();
    }
}
