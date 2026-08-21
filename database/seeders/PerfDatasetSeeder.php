<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds ~3,000 synthetic contracts (plus party and approval rows) into
 * apollo_contracts_expense so the contracts dashboard can be measured at
 * production-like N. See .scratch/contracts-dashboard-perf/issues/04-seed-realistic-dataset.md
 *
 * IMPORTANT - run with HTTP_HOST set, e.g.
 *
 *   HTTP_HOST=apollo.contracts.legality:8888 php artisan db:seed --class=PerfDatasetSeeder
 *
 * config/app.php builds APP_ENCRYPTION_KEY from $_SERVER['HTTP_HOST'] ("c0n|r@(t$" . firstHostLabel . "4").
 * Under the web server that is "c0n|r@(t$apollo4" (exactly 16 bytes, as AES-128-CBC requires).
 * Without HTTP_HOST the CLI falls back to "localhost", producing a 19-byte key that both fails
 * to construct an Encrypter and would write values the web app could never decrypt. The seeder
 * asserts the key is correct before writing anything.
 *
 * Seeded rows are identifiable three ways, none of which needs a schema change:
 *   - contracts.id / contract_party_data.id / approval_contracts.id in the 100001+ range
 *     (explicitly assigned; all pre-existing ids are < 200)
 *   - contracts.contract_unique_id LIKE 'SEEDPERF-%'
 *   - contract_party_data.party_address LIKE '%[SEEDPERF]%'
 *   - approval_contracts.unique_id LIKE 'seedperf%'
 *
 * Nothing pre-existing is read for mutation or altered. Only INSERTs.
 * Reverse with: php artisan db:seed --class=PerfDatasetRollbackSeeder
 */
class PerfDatasetSeeder extends Seeder
{
    /** First id used for every table we insert into. Pre-existing ids are all < 200. */
    public const ID_BASE = 100001;

    public const MARKER = 'SEEDPERF';

    /** contract_status => count. 'Pre-Approval' folds into 'review' via contractStatusKey(). */
    private const STATUS_PLAN = [
        'Draft'         => 480,
        'Review'        => 330,
        'Pre-Approval'  => 150,
        'Finalization'  => 180,
        'Negotiation'   => 240,
        'Approval'      => 270,
        'Approved'      => 210,
        'Signing'       => 240,
        'Executed'      => 900,
    ];

    /**
     * substatus values for Executed contracts. 'Terminated' is deliberately capitalised while
     * the rest are lowercase: the PHP switch in ContractDashboardController is case-sensitive
     * but MySQL's utf8mb4_unicode_ci collation is not, so a later GROUP BY rewrite would look
     * correct against uniformly-cased data while actually changing the numbers.
     */
    private const EXECUTED_SUBSTATUS_PLAN = [
        'active'     => 400,
        'expired'    => 150,
        'pending'    => 100,
        'renewed'    => 90,
        'Terminated' => 90,
        'completed'  => 70,
    ];

    /** Non-executed stages keep the mixed-case free-text substatus the real rows use. */
    private const STAGE_SUBSTATUS = [
        'Draft'        => 'Initial Draft',
        'Review'       => 'Awaiting Owner Next Level',
        'Pre-Approval' => 'Review',
        'Finalization' => 'Under Finalization',
        'Negotiation'  => 'Under Process',
        'Approval'     => 'Pending Approval',
        'Approved'     => 'Approved',
        'Signing'      => 'Approved',
    ];

    /** How many approval_contracts rows a contract at each stage has walked through. */
    private const APPROVAL_ROWS_PER_STAGE = [
        'Draft'        => 1,
        'Review'       => 3,
        'Pre-Approval' => 3,
        'Finalization' => 4,
        'Negotiation'  => 4,
        'Approval'     => 5,
        'Approved'     => 5,
        'Signing'      => 6,
        'Executed'     => 7,
    ];

    private const CHUNK = 200;

    public function run(): void
    {
        $this->assertEncryptionKey();
        $this->assertNotAlreadySeeded();

        mt_srand(20260814); // deterministic output

        $pools = $this->loadPools();

        $this->command->info('Building contract rows...');
        [$contracts, $meta] = $this->buildContracts($pools);

        $this->command->info('Inserting ' . count($contracts) . ' contracts...');
        $this->insertChunked('contracts', $contracts);
        unset($contracts);

        $parties = $this->buildParties($meta, $pools);
        $this->command->info('Inserting ' . count($parties) . ' contract_party_data rows...');
        $this->insertChunked('contract_party_data', $parties);
        unset($parties);

        $approvals = $this->buildApprovals($meta);
        $this->command->info('Inserting ' . count($approvals) . ' approval_contracts rows...');
        $this->insertChunked('approval_contracts', $approvals);
        unset($approvals);

        $this->report();
    }

    // ---------------------------------------------------------------- guards

    private function assertEncryptionKey(): void
    {
        $key = (string) config('app.APP_ENCRYPTION_KEY');

        if (strlen($key) !== 16) {
            throw new \RuntimeException(
                "APP_ENCRYPTION_KEY is " . strlen($key) . " bytes ('{$key}'), not the 16 AES-128-CBC needs. " .
                "config/app.php derives it from \$_SERVER['HTTP_HOST']. Re-run with:\n" .
                "  HTTP_HOST=apollo.contracts.legality:8888 php artisan db:seed --class=PerfDatasetSeeder"
            );
        }

        $probe = encryptString('probe', 'probe');
        if (strpos($probe, 'ey') !== 0 || decryptString($probe, 'probe') !== 'probe') {
            throw new \RuntimeException('encryptString()/decryptString() did not round-trip; refusing to write.');
        }
    }

    private function assertNotAlreadySeeded(): void
    {
        $existing = DB::table('contracts')->where('contract_unique_id', 'like', self::MARKER . '-%')->count();

        if ($existing > 0) {
            throw new \RuntimeException(
                "Found {$existing} rows already marked '" . self::MARKER . "'. Roll back first:\n" .
                "  HTTP_HOST=apollo.contracts.legality:8888 php artisan db:seed --class=PerfDatasetRollbackSeeder"
            );
        }
    }

    // ---------------------------------------------------------------- pools

    /**
     * Read-only lookups of the real reference rows. The query builder is used throughout
     * rather than Eloquent: Contract::boot() adds a select('*') global scope plus
     * $with=['contractPartyList'], and BranchUser/EntityBusiness carry BranchScope /
     * DepartmentScope, all of which read the HTTP session and are meaningless in the CLI.
     */
    private function loadPools(): array
    {
        // The dashboard's visibility filter resolves branches through BranchUser (BranchScope
        // pins entityid to the session entity, which is 2 locally) and departments through
        // EntityBusiness (DepartmentScope adds applicable=1 plus the same entityid).
        $accessibleBranches = DB::table('branch')->where('entityid', 2)->orderBy('id')->pluck('id')->all();
        $otherBranches      = DB::table('branch')->where('entityid', '<>', 2)->orderBy('id')->pluck('id')->all();

        // Departments stay inside the visible set so the department filter has 30+ real values
        // to filter on without adding a second axis of invisibility on top of the branch one.
        $departments = DB::table('entitybusiness')
            ->where('entityid', 2)->where('applicable', 1)
            ->orderBy('id')->pluck('id')->all();

        $contractTypes = DB::table('contract_type')->orderBy('contract_type_id')->pluck('contract_type_id')->all();
        $categories    = DB::table('contract_categories')->orderBy('id')->pluck('id')->all();
        $entities      = DB::table('entity')->orderBy('id')->pluck('id')->all();

        foreach (['accessibleBranches', 'otherBranches', 'departments', 'contractTypes', 'categories', 'entities'] as $name) {
            if (empty($$name)) {
                throw new \RuntimeException("Reference pool '{$name}' is empty; cannot build plausible rows.");
            }
        }

        return compact('accessibleBranches', 'otherBranches', 'departments', 'contractTypes', 'categories', 'entities');
    }

    // ---------------------------------------------------------------- contracts

    private function buildContracts(array $pools): array
    {
        $vendors = [
            'Sundaram Facilities', 'Kavin Medtech', 'Orion Diagnostics', 'Blue Ridge Logistics',
            'Aster Pharma Supply', 'Zenith Security Services', 'Nova Biomedical', 'Cauvery Catering',
            'Helix IT Solutions', 'Pinnacle Housekeeping', 'Everest Elevators', 'Meridian Radiology',
        ];
        $endTypes    = ['onetimeContract', 'fixedTerm', 'autoRenewal'];
        $priorities  = ['low', 'medium', 'high', 'critical'];

        // Encrypt the small set of repeated values once instead of 3,000 times each.
        $encEndType  = array_map(fn ($v) => encryptString($v, 'end_contract_type'), array_combine($endTypes, $endTypes));
        $encCurrency = encryptString('INR', 'currency');
        $encMode     = encryptString('new', 'contract_mode');

        $stages = [];
        foreach (self::STATUS_PLAN as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $stages[] = $status;
            }
        }

        $executedSubstatuses = [];
        foreach (self::EXECUTED_SUBSTATUS_PLAN as $sub => $count) {
            for ($i = 0; $i < $count; $i++) {
                $executedSubstatuses[] = $sub;
            }
        }

        if (count($executedSubstatuses) !== self::STATUS_PLAN['Executed']) {
            throw new \RuntimeException('Executed substatus plan does not sum to the Executed contract count.');
        }

        $rows = [];
        $meta = [];
        $executedSeen = 0;
        $accIdx = 0;
        $othIdx = 0;

        foreach ($stages as $n => $status) {
            $id  = self::ID_BASE + $n;
            $seq = str_pad((string) ($n + 1), 6, '0', STR_PAD_LEFT);

            $substatus = $status === 'Executed'
                ? $executedSubstatuses[$executedSeen++]
                : self::STAGE_SUBSTATUS[$status];

            $vendor  = $vendors[$n % count($vendors)];
            $typeId  = $pools['contractTypes'][$n % count($pools['contractTypes'])];
            $deptId  = $pools['departments'][$n % count($pools['departments'])];
            $endType = $endTypes[$n % 3];

            $name  = sprintf('%s Agreement - %s (Seed %s)', $endType === 'autoRenewal' ? 'Service' : 'Supply', $vendor, $seq);
            $value = 25000 + (($n * 4137) % 9750000);

            // Dates spread over a few years so expiry/renewal views have something to show.
            $start = strtotime('2022-01-01 +' . (($n * 7) % 1460) . ' days');
            $end   = strtotime('+' . (12 + ($n % 48)) . ' months', $start);

            // Every 10th contract gets NO internal party. Those are silently dropped from
            // every dashboard counter by Controller.php:221 and that must stay testable.
            $hasInternal = ($n % 10) !== 9;
            // 15% of the internal-party contracts sit in a branch outside the session
            // entity, so the branch-access exclusion is exercised too.
            $useAccessibleBranch = ($n % 100) < 85;

            if ($hasInternal) {
                if ($useAccessibleBranch) {
                    $branchId = $pools['accessibleBranches'][$accIdx++ % count($pools['accessibleBranches'])];
                } else {
                    $branchId = $pools['otherBranches'][$othIdx++ % count($pools['otherBranches'])];
                }
            } else {
                $branchId = null;
            }

            $meta[] = [
                'id'            => $id,
                'status'        => $status,
                'has_internal'  => $hasInternal,
                'branch_id'     => $branchId,
                'extra_branch'  => $hasInternal && ($n % 5) < 2,
                'entity_id'     => $pools['entities'][$n % count($pools['entities'])],
                'created_at'    => date('Y-m-d H:i:s', $start),
            ];

            $rows[] = [
                'id'                   => $id,
                'contract_mode'        => $encMode,
                'contract_name'        => encryptString($name, 'contract_name'),
                'contract_type'        => (string) $typeId,
                'contract_description' => 'Synthetic row generated by PerfDatasetSeeder for dashboard performance measurement.',
                'commencement_type'    => 'fixedDate',
                'fixed_date'           => date('Y-m-d', $start),
                'contract_end_date'    => date('Y-m-d', $end),
                'end_contract_type'    => $encEndType[$endType],
                'onetime_end_date'     => $endType === 'onetimeContract' ? date('Y-m-d', $end) : null,
                'fixedterm_end_date'   => $endType === 'fixedTerm' ? date('Y-m-d', $end) : null,
                'renewal_type'         => $endType === 'autoRenewal' ? 'auto' : null,
                'signing_date'         => in_array($status, ['Signing', 'Executed'], true) ? date('Y-m-d', $start) : null,
                'currency'             => $encCurrency,
                'currency_value'       => encryptString((string) $value, 'currency_value'),
                'billing_value'        => (string) $value,
                'total_value'          => (string) $value,
                'currency_contract'    => 'INR',
                'billing_frequency'    => ['monthly', 'quarterly', 'annually'][$n % 3],
                'catgoery_id'          => (string) $pools['categories'][$n % count($pools['categories'])],
                'department_id'        => $deptId,
                'owner'                => 1507,
                'created_by'           => 1507,
                'legal_advisor_email'  => null,
                'legal_contact_status' => 'not_contacted',
                'contract_status'      => $status,
                'substatus'            => $substatus,
                'preapproval_stage'    => $status === 'Pre-Approval' ? 'preapproval' : null,
                'renewtype'            => 'renew',
                'parentcontract'       => 0,
                'status'               => 1,
                'storage_type'         => 'Local',
                'contract_unique_id'   => self::MARKER . '-' . $seq,
                'contract_priority'    => $priorities[$n % 4],
                'contract_name_hash'   => hash('sha256', $name),
                'tenure'               => (12 + ($n % 48)) . ' months',
                'created_at'           => date('Y-m-d H:i:s', $start),
                'updated_at'           => date('Y-m-d H:i:s', $start),
            ];
        }

        return [$rows, $meta];
    }

    // ---------------------------------------------------------------- parties

    private function buildParties(array $meta, array $pools): array
    {
        $rows = [];
        $id   = self::ID_BASE;
        $n    = 0;

        foreach ($meta as $c) {
            if ($c['has_internal']) {
                $rows[] = [
                    'id'                         => $id++,
                    'custom_field_group_id'      => $c['id'],
                    'contract_party_type'        => 'Internal',
                    'party_sub_type'             => 'Internal',
                    'contract_party_id'          => $c['entity_id'],
                    'contract_party_exe_id'      => null,
                    'contract_party_location_id' => (string) $c['branch_id'],
                    'vendor_code'                => null,
                    'party_address'              => 'Seeded internal party address [' . self::MARKER . ']',
                    'contact_details'            => null,
                ];

                if ($c['extra_branch']) {
                    // A second internal party in a different branch - real contracts are
                    // frequently multi-location and the party loop must handle it.
                    $alt = $pools['accessibleBranches'][($n + 17) % count($pools['accessibleBranches'])];
                    $rows[] = [
                        'id'                         => $id++,
                        'custom_field_group_id'      => $c['id'],
                        'contract_party_type'        => 'Internal',
                        'party_sub_type'             => 'Internal',
                        'contract_party_id'          => $c['entity_id'],
                        'contract_party_exe_id'      => null,
                        'contract_party_location_id' => (string) $alt,
                        'vendor_code'                => null,
                        'party_address'              => 'Seeded internal party address [' . self::MARKER . ']',
                        'contact_details'            => null,
                    ];
                }
            }

            // Every contract has an external counterparty, internal party or not.
            $rows[] = [
                'id'                         => $id++,
                'custom_field_group_id'      => $c['id'],
                'contract_party_type'        => 'External',
                'party_sub_type'             => 'organization',
                'contract_party_id'          => $c['entity_id'],
                'contract_party_exe_id'      => 1,
                'contract_party_location_id' => null,
                'vendor_code'                => 'VC' . str_pad((string) ($n % 500), 4, '0', STR_PAD_LEFT),
                'party_address'              => 'Seeded external party address [' . self::MARKER . ']',
                'contact_details'            => null,
            ];

            $n++;
        }

        return $rows;
    }

    // ---------------------------------------------------------------- approvals

    private function buildApprovals(array $meta): array
    {
        // email => display name. The app stores username as encrypted JSON {email,name} - see
        // ContractDashboardController::actionableItemCounts() and the 13 blade sites that print
        // json_decode($row->username)->name. Seeding a bare email made every one of those read
        // empty, and made the dashboard counter skip the row entirely (ticket 17, 2026-08-21).
        $users = [
            'jeevanantham@legalitysimplified.com' => 'Jeeva',
            'owner.one@example.com'               => 'Owner One',
            'approver.one@example.com'            => 'Approver One',
            'approver.two@example.com'            => 'Approver Two',
            'verifier.one@example.com'            => 'Verifier One',
            'signatory.one@example.com'           => 'Signatory One',
        ];
        $userEmails = array_keys($users);
        $roles      = ['Owner', 'Approver', 'Approver', 'Verifier', 'Recommender', 'Approver', 'Signatory'];

        // status / previous_status really are capitalised in this database - measured over the 127
        // real rows: Approved, Draft, Pending, Rejected, Signing, Negotiation, review.
        $statuses = ['Approved', 'Pending', 'Completed', 'Sent'];

        // approval_status is NOT the same vocabulary. Every write site in the app passes a
        // lowercase word - 'pending', 'approved', 'rejected' - and the dashboard counter compares
        // with === 'pending'. Seeding 'Pending' here meant the counter matched nothing on 13,740
        // rows, so every number measured against the seeded set came from the wrong population.
        $approvalStatuses = ['approved', 'pending'];

        // Encrypt the repeated values once - there are ~14k rows x 4 encrypted columns.
        $encUser = [];
        foreach ($users as $email => $name) {
            $encUser[$email] = encryptString(json_encode(['email' => $email, 'name' => $name]), 'username');
        }
        $encStatus = [];
        foreach ($statuses as $s) {
            $encStatus[$s] = encryptString($s, 'status');
        }
        $encApprovalStatus = [];
        foreach ($approvalStatuses as $s) {
            $encApprovalStatus[$s] = encryptString($s, 'approval_status');
        }

        $rows = [];
        $id   = self::ID_BASE;
        $n    = 0;

        foreach ($meta as $c) {
            $count = self::APPROVAL_ROWS_PER_STAGE[$c['status']];

            for ($o = 0; $o < $count; $o++) {
                $user     = $userEmails[($n + $o) % count($userEmails)];
                $isLast   = ($o === $count - 1 && $c['status'] !== 'Executed');
                $status   = $statuses[$isLast ? 1 : 0];
                $apprStat = $approvalStatuses[$isLast ? 1 : 0];

                $rows[] = [
                    'id'                    => $id++,
                    'username'              => $encUser[$user],
                    'approval_type_main'    => 'sequential',
                    'flow_type'             => $o === 0 ? 'approval' : 'approval',
                    'next_group_on_approve' => '',
                    'next_group_on_reject'  => '',
                    'approver_type_row'     => $roles[$o % count($roles)],
                    'intimation_only'       => 0,
                    'approval_type_row'     => 'sequential',
                    'status'                => $encStatus[$status],
                    'previous_status'       => $encStatus[$statuses[2]],
                    'contract_id'           => $c['id'],
                    'approval_status'       => $encApprovalStatus[$apprStat],
                    'orderval'              => $o,
                    // Grouped by unique_id in the controller, so it must vary per stage row.
                    'unique_id'             => 'seedperf_' . $c['id'] . '_' . $o,
                    'auto_next_enabled'     => 0,
                    'awaiting_owner_trigger'=> 0,
                    'flag'                  => 0,
                    'superseded'            => 0,
                    'row_status'            => 1,
                    'fileType'              => 'Local', // NOT NULL enum with no default
                    'created_by'            => json_encode(['email' => $user, 'name' => $users[$user]]),
                    'updated_by'            => json_encode(['email' => $user, 'name' => $users[$user]]),
                    'created_at'            => $c['created_at'],
                    'updated_at'            => $c['created_at'],
                    'updated_on'            => $c['created_at'],
                ];
            }

            $n++;
        }

        return $rows;
    }

    // ---------------------------------------------------------------- plumbing

    private function insertChunked(string $table, array $rows): void
    {
        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    private function report(): void
    {
        $this->command->info('');
        $this->command->info('contracts             : ' . DB::table('contracts')->count()
            . ' (seeded ' . DB::table('contracts')->where('contract_unique_id', 'like', self::MARKER . '-%')->count() . ')');
        $this->command->info('contract_party_data   : ' . DB::table('contract_party_data')->count()
            . ' (seeded ' . DB::table('contract_party_data')->where('party_address', 'like', '%[' . self::MARKER . ']%')->count() . ')');
        $this->command->info('approval_contracts    : ' . DB::table('approval_contracts')->count()
            . ' (seeded ' . DB::table('approval_contracts')->where('unique_id', 'like', 'seedperf%')->count() . ')');
    }
}
