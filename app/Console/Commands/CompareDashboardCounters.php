<?php

namespace App\Console\Commands;

use App\Helpers\Helpers;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Modules\Contract\Http\Controllers\ContractDashboardController;

/**
 * Old counters against new counters, same data, several users and roles.
 *
 * Throwaway. It exists for one job: proving dashboardSummary() gives the same 15 stage
 * counters and 4 task counters as dashDetails() before the old method is deleted
 * (spec.md section 10 step 11, names.md section 7). Delete it with the old method.
 *
 *   php artisan dashboard:compare-counters --host=apollo.contracts.legality \
 *       --as="someone@example.com|Super Admin" --as="other@example.com|User"
 *
 * Two things it cannot do, and says so rather than pretending:
 *
 *  - Both encryption keys are derived from $_SERVER['HTTP_HOST'] (config/app.php:7), which is
 *    'localhost' from a bare CLI: APP_ENCRYPTION_KEY, which the PHP Encrypter will not
 *    construct on, and APP_LEGACY_KEY, which every AES_DECRYPT() written into a query uses.
 *    So --host is required and both are rebuilt from it here, exactly as config/app.php does.
 *    Get this wrong and the user lookup silently matches nobody.
 *  - The OLD "My Actionable Items" numbers are computed in the blade, not in dashDetails(),
 *    so there is nothing in the old controller to compare against. Those six numbers are
 *    reported, not diffed. They are also the one difference the spec expects.
 */
class CompareDashboardCounters extends Command
{
    protected $signature = 'dashboard:compare-counters
        {--host=apollo.contracts.legality : host the encryption key is derived from}
        {--entity= : contractSessionEntity to use; defaults to default_entity_id}
        {--as=* : one or more "username|role" pairs to run as}
        {--locs= : comma separated contractlocs filter to apply to both}
        {--types= : comma separated contracttype filter to apply to both}';

    protected $description = 'Compare dashDetails() counters against dashboardSummary() counters, old beside new';

    /** The 15 stage counters, in the order the view prints them. */
    private const STAGE_KEYS = [
        'all', 'draft', 'review', 'finalization', 'negotiation', 'approval', 'approved',
        'signing', 'executed', 'executed_active', 'executed_expired', 'executed_pending',
        'executed_renewed', 'executed_terminated', 'executed_completed',
    ];

    private const TASK_KEYS = ['all', 'pending', 'inprogress', 'completed'];

    public function handle(): int
    {
        $this->applyHostEncryptionKey((string) $this->option('host'));

        $pairs = $this->option('as');

        if (empty($pairs)) {
            $this->error('Nothing to run. Pass at least one --as="username|role".');

            return self::FAILURE;
        }

        $differences = 0;
        $compared = 0;
        $skipped = 0;

        foreach ($pairs as $pair) {
            [$username, $role] = array_pad(explode('|', $pair, 2), 2, '');

            if ($username === '' || $role === '') {
                $this->error('Bad --as value: ' . $pair . ' (expected "username|role")');

                return self::FAILURE;
            }

            $result = $this->compareOne(trim($username), trim($role));

            if ($result === null) {
                $skipped++;
                continue;
            }

            $compared++;
            $differences += $result;
        }

        $this->newLine();
        $this->line('compared: ' . $compared . ', skipped: ' . $skipped);

        if ($compared === 0) {
            $this->error('Nothing was actually compared. This is not a pass.');

            return self::FAILURE;
        }

        if ($differences === 0) {
            $this->info('No unexpected difference. The stage and task counters match everywhere.');

            return self::SUCCESS;
        }

        $this->error($differences . ' counter(s) differ. The old method must not be deleted yet.');

        return self::FAILURE;
    }

    /**
     * Rebuild APP_ENCRYPTION_KEY the way config/app.php does, from the first label of the
     * host. Without this every decryptString() call in the old path throws, because a bare
     * CLI has no HTTP_HOST.
     */
    private function applyHostEncryptionKey(string $host): void
    {
        $firstLabel = explode('.', $host)[0];

        Config::set('app.APP_ENCRYPTION_KEY', 'c0n|r@(t$' . $firstLabel . '4');

        // The legacy key is the one baked into every AES_DECRYPT() this app writes into SQL
        // (config/app.php:203). It is host-derived too, so without this the user lookup matches
        // nobody and a run "passes" having compared nothing.
        Config::set('app.APP_LEGACY_KEY', 'G0@L-Pr0' . $firstLabel . 'common');

        $this->line('Both keys derived from host: ' . $host);
    }

    /**
     * @return int|null  number of differing counters, or null when the user could not be run
     */
    private function compareOne(string $username, string $role): ?int
    {
        $this->newLine();
        $this->line('== ' . $username . ' as ' . $role);

        session()->put('contractSessionUser', $username);
        session()->put('contractSessionUserRole', $role);
        session()->put('contractSessionEntity', $this->option('entity') ?: env('default_entity_id'));
        session()->forget('contractSessionExUser');

        $user = Helpers::userInfo();

        if (!$user || !isset($user->id)) {
            $this->warn('  skipped: no AddUsers row for that username in this entity');

            return null;
        }

        session()->put('contractUserId', $user->id);

        $request = $this->buildRequest();

        $controller = new ContractDashboardController();

        $old = $controller->dashDetails($request)->getData();
        $new = $controller->dashboardSummary($request)->getData();

        $differences = 0;

        // Printed so a run that compared only zeroes cannot read as a pass.
        $this->line(sprintf(
            '  contracts counted: old=%s new=%s   tasks: old=%s new=%s',
            $old['counts']['all'] ?? 'null',
            $new['counts']['all'] ?? 'null',
            $old['stusMyTask']['all'] ?? 'null',
            $new['stusMyTask']['all'] ?? 'null'
        ));

        foreach (self::STAGE_KEYS as $key) {
            $differences += $this->reportKey('counts', $key, $old['counts'][$key] ?? null, $new['counts'][$key] ?? null);
        }

        foreach (self::TASK_KEYS as $key) {
            $differences += $this->reportKey('tasks', $key, $old['stusMyTask'][$key] ?? null, $new['stusMyTask'][$key] ?? null);
        }

        // Not a diff. The old six numbers are produced by the blade, so there is nothing here
        // to compare them with, and the spec expects them to change anyway.
        $this->line('  actionable items (new only, expected to change): ' . json_encode($new['stusMy']));

        if ($differences === 0) {
            $this->info('  stage and task counters match');
        }

        return $differences;
    }

    private function buildRequest(): Request
    {
        $input = [];

        if ($this->option('locs')) {
            $input['contractlocs'] = explode(',', (string) $this->option('locs'));
        }

        if ($this->option('types')) {
            $input['contracttype'] = explode(',', (string) $this->option('types'));
        }

        return new Request($input);
    }

    private function reportKey(string $group, string $key, $old, $new): int
    {
        if ($old === $new) {
            return 0;
        }

        $this->error(sprintf('  %s[%s] old=%s new=%s', $group, $key, var_export($old, true), var_export($new, true)));

        return 1;
    }
}
