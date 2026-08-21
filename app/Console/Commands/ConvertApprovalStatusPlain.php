<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rewrite approval_contracts.approval_status from AES-128-CBC ciphertext to the readable
 * lowercase word it decrypts to, so SQL can filter, group and index it.
 *
 * See .scratch/contracts-dashboard-perf/issues/17-plain-columns-experiment.md
 *
 *   php artisan contract:convert-approval-status              # checks only, changes nothing
 *   php artisan contract:convert-approval-status --apply      # rewrites the rows
 *
 * Not a migration, by the same reasoning as contract:convert-party-data: this is a data
 * rewrite that has to decrypt in PHP with the right key, which no migration can guarantee.
 * The column narrowing and the index that follow it ARE a migration -
 * 2026_08_21_000001_narrow_approval_contracts_approval_status.php - and must run after this.
 *
 * Safe to stop and re-run. It only touches rows whose value still starts with 'ey', so a
 * second run over converted rows is a no-op, and a run interrupted half way leaves a table
 * that every read site already copes with: decryptString() decrypts a value starting with
 * 'ey' and returns anything else untouched.
 *
 * Refuses to do anything until every check passes:
 *
 *  - it is pointed at apollo_contracts_expense and not at goalapp_apollo or any other
 *    database (CLAUDE.md)
 *  - the encryption key is the web server's, proved by decrypting a real row rather than by
 *    reading a config value. Without HTTP_HOST the CLI key is "c0n|r@(t$localhost4", which
 *    decrypts nothing - see config/app.php:202
 *  - every single value decrypts, and there is no --skip-failures escape hatch. A row that
 *    will not decrypt stops the run before anything is written, because the alternative is a
 *    row silently left as ciphertext in a column the new counter reads as plain text
 *  - every decrypted value is one of the words the application actually writes, and none of
 *    them starts with 'ey' or is longer than the target width
 *
 * Reverse with --down, which re-encrypts. That works because the values are a closed set of
 * five short words, not free text: nothing is lost by decrypting and encrypting again. Run
 * the migration's down() first, so the column is wide enough to hold ciphertext.
 */
class ConvertApprovalStatusPlain extends Command
{
    protected $signature = 'contract:convert-approval-status
        {--apply : actually run it; without this the command only checks}
        {--down : re-encrypt the plain values instead, undoing a previous run}
        {--chunk=1000 : rows per chunk}';

    protected $description = 'Rewrite approval_contracts.approval_status from ciphertext to plain text';

    private const TABLE = 'approval_contracts';

    private const COLUMN = 'approval_status';

    /** Width the migration narrows the column to. Longest real value is 8 characters. */
    private const TARGET_WIDTH = 20;

    /**
     * Every value the application writes to this column. Gathered from all 61 write sites:
     * 'pending', 'approved', 'rejected', plus $appType and $userInputVal at
     * ContractController.php:7632, :7853, :8966 and :11114 which carry an approval type.
     * A decrypted value outside this list stops the run - it means the column holds
     * something this ticket did not account for.
     */
    private const EXPECTED = [
        'pending', 'approved', 'rejected', 'completed', 'sent', 'draft',
        'edit', 'legacy', 'legacy_edit', 'renewed', 'ext2', 'addendum', 'terminate', '',
    ];

    /** Databases this command must never change, whatever it is pointed at (CLAUDE.md). */
    private const ONLY = 'apollo_contracts_expense';

    public function handle(): int
    {
        $down  = (bool) $this->option('down');
        $apply = (bool) $this->option('apply');
        $chunk = max(1, (int) $this->option('chunk'));

        $this->info($down
            ? 'approval_status: plain text -> ciphertext (--down)'
            : 'approval_status: ciphertext -> plain text');
        $this->newLine();

        $database = DB::connection()->getDatabaseName();

        if ($database !== self::ONLY) {
            $this->error("Refusing to run. Connected to '{$database}'; this command only ever touches '" . self::ONLY . "'.");

            return self::FAILURE;
        }

        $this->line("  database        : {$database}");

        // Seatbelt: prove the key is the web server's by decrypting a real row, not by
        // reading config. A wrong key here would write garbage over every row.
        if (!$this->keyIsUsable()) {
            return self::FAILURE;
        }

        $survey = $this->survey($down);

        if ($survey === null) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('  rows total      : ' . $survey['total']);
        $this->line('  already done    : ' . $survey['done']);
        $this->line('  to convert      : ' . $survey['todo']);
        $this->line('  distinct values : ' . implode(', ', array_map(
            fn ($v, $n) => ($v === '' ? "''" : $v) . " ({$n})",
            array_keys($survey['values']),
            $survey['values']
        )));
        $this->line('  longest value   : ' . $survey['longest'] . ' chars (target width ' . self::TARGET_WIDTH . ')');
        $this->newLine();

        if ($survey['todo'] === 0) {
            $this->info('  Nothing to do.');

            return self::SUCCESS;
        }

        if (!$apply) {
            $this->warn('  Checks only - nothing was written.');
            $this->line('  Add --apply to run it. Take a backup first.');
            if (!$down) {
                $this->line('  Then run the migration that narrows the column and indexes it:');
                $this->line('    php artisan migrate --path=database/migrations/2026_08_21_000001_narrow_approval_contracts_approval_status.php');
            }

            return self::SUCCESS;
        }

        $written = $this->rewrite($down, $chunk);

        $this->newLine();
        $this->info("  Wrote {$written} rows.");

        $after = $this->survey($down);

        if ($after === null || $after['todo'] !== 0) {
            $this->error('  Verification failed: rows are still unconverted. Run it again.');

            return self::FAILURE;
        }

        $this->info('  Verified: every row is in the target form.');

        Log::info('approval_status conversion finished', [
            'direction' => $down ? 'encrypt' : 'decrypt',
            'rows'      => $written,
        ]);

        return self::SUCCESS;
    }

    /**
     * Decrypt one real ciphertext row. If the key is wrong this throws, and it is far better
     * to find that out here than after 13,861 rows have been overwritten.
     */
    private function keyIsUsable(): bool
    {
        $sample = DB::table(self::TABLE)
            ->whereRaw('LEFT(' . self::COLUMN . ", 2) = 'ey'")
            ->value(self::COLUMN);

        if ($sample === null) {
            $this->line('  key check       : skipped, no ciphertext rows left');

            return true;
        }

        try {
            $value = decryptString($sample, self::COLUMN);
        } catch (\Throwable $e) {
            $this->error('  key check       : FAILED');
            $this->newLine();
            $this->line('  A ciphertext row would not decrypt, so the key is not the one that wrote it.');
            $this->line('  config/app.php builds the key from $_SERVER[\'HTTP_HOST\']. Under the CLI that is');
            $this->line('  "localhost" unless you set it. Run:');
            $this->newLine();
            $this->line('    HTTP_HOST=apollo.contracts.legality php artisan ' . $this->getName() . ' ' . implode(' ', array_filter([
                $this->option('apply') ? '--apply' : null,
                $this->option('down') ? '--down' : null,
            ])));

            return false;
        }

        $this->line('  key check       : ok (a real row decrypts to "' . $value . '")');

        return true;
    }

    /**
     * Read every row and report what is there. Deliberately reads all of them rather than
     * sampling: the run is refused unless every value is accounted for.
     *
     * Returns null when something is wrong, having already said what.
     */
    private function survey(bool $down): ?array
    {
        $total   = 0;
        $done    = 0;
        $todo    = 0;
        $values  = [];
        $longest = 0;
        $bad     = [];

        DB::table(self::TABLE)
            ->select('id', self::COLUMN)
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use ($down, &$total, &$done, &$todo, &$values, &$longest, &$bad) {
                foreach ($rows as $row) {
                    $total++;

                    $raw          = (string) $row->{self::COLUMN};
                    $isCiphertext = strpos(trim($raw), 'ey') === 0;

                    // Already in the target form?
                    if ($down ? $isCiphertext : !$isCiphertext) {
                        $done++;
                        if (!$down) {
                            $this->account($raw, $values, $longest, $bad, $row->id);
                        }

                        continue;
                    }

                    $todo++;

                    if ($down) {
                        $this->account($raw, $values, $longest, $bad, $row->id);

                        continue;
                    }

                    try {
                        $plain = decryptString($raw, self::COLUMN);
                    } catch (\Throwable $e) {
                        $bad[] = "id {$row->id}: will not decrypt";

                        continue;
                    }

                    $this->account($plain, $values, $longest, $bad, $row->id);
                }
            });

        if ($bad !== []) {
            $this->error('  ' . count($bad) . ' row(s) failed their check. Nothing was written.');
            foreach (array_slice($bad, 0, 10) as $line) {
                $this->line('    ' . $line);
            }
            if (count($bad) > 10) {
                $this->line('    ... and ' . (count($bad) - 10) . ' more');
            }

            return null;
        }

        return compact('total', 'done', 'todo', 'values', 'longest');
    }

    /**
     * Check one plain value and count it. A value outside EXPECTED, longer than the target
     * width, or starting with 'ey' is recorded as a failure - the last of those because
     * decryptString() would try to decrypt it on the way back out.
     */
    private function account(string $plain, array &$values, int &$longest, array &$bad, $id): void
    {
        $values[$plain] = ($values[$plain] ?? 0) + 1;
        $longest        = max($longest, strlen($plain));

        if (!in_array(strtolower($plain), self::EXPECTED, true)) {
            $bad[] = "id {$id}: unexpected value '{$plain}'";
        }

        if (strlen($plain) > self::TARGET_WIDTH) {
            $bad[] = "id {$id}: '{$plain}' is " . strlen($plain) . " chars, wider than " . self::TARGET_WIDTH;
        }

        if (strpos($plain, 'ey') === 0) {
            $bad[] = "id {$id}: '{$plain}' starts with 'ey', which decryptString() would try to decrypt";
        }
    }

    /**
     * Rewrite the rows, one chunk at a time, one UPDATE per row keyed on the primary key.
     *
     * chunkById(), never chunk(), because the rows are being changed under the cursor.
     * One UPDATE per row rather than a CASE over a whereIn list, because a whereIn with
     * 1,000 or more bound parameters silently returns zero rows on this MariaDB build -
     * see CONTEXT.md. The row count here is 5 figures, so the cost is acceptable.
     */
    private function rewrite(bool $down, int $chunk): int
    {
        $written = 0;

        $bar = $this->output->createProgressBar();
        $bar->start();

        DB::table(self::TABLE)
            ->select('id', self::COLUMN)
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use ($down, &$written, $bar) {
                foreach ($rows as $row) {
                    $raw          = (string) $row->{self::COLUMN};
                    $isCiphertext = strpos(trim($raw), 'ey') === 0;

                    if ($down ? $isCiphertext : !$isCiphertext) {
                        continue;
                    }

                    $value = $down
                        ? encryptString($raw, self::COLUMN)
                        : decryptString($raw, self::COLUMN);

                    DB::table(self::TABLE)
                        ->where('id', $row->id)
                        ->update([self::COLUMN => $value]);

                    $written++;
                    $bar->advance();
                }
            });

        $bar->finish();

        return $written;
    }
}
