<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds ~3,000 synthetic contracts (plus party and approval rows) into
 * apollo_contracts_expense so the contracts dashboard and the contract detail page can be
 * measured at production-like N.
 * See .scratch/contracts-dashboard-perf/issues/04-seed-realistic-dataset.md and
 * .scratch/contract-detail-page-perf/issues/02-seed-realistic-contract-rows.md
 *
 * A column is filled here because the dashboard or the detail page reads it. A NULL column is
 * not a neutral placeholder: the page either crashes on it (explode() on a NULL reminder), or
 * skips the block that reads it, which makes the measurement come from the wrong population.
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

    /**
     * A contract row now carries ~25 encrypted columns plus a 1.5 KB rules_id payload, about
     * 7 KB in all. 200 of them overrun MySQL's 1 MB max_allowed_packet, so contracts insert in
     * smaller batches than the party and approval rows.
     */
    private const CHUNK_CONTRACTS = 40;

    public function run(): void
    {
        $this->assertEncryptionKey();
        $this->assertNotAlreadySeeded();

        mt_srand(20260814); // deterministic output

        $pools = $this->loadPools();

        $this->command->info('Building contract rows...');
        [$contracts, $meta] = $this->buildContracts($pools);

        $this->command->info('Inserting ' . count($contracts) . ' contracts...');
        $this->insertChunked('contracts', $contracts, self::CHUNK_CONTRACTS);
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

        // The signatory select on the edit tab lists every AddUsers row (table ContractUsers) and
        // marks the option whose id equals contracts.signatory. A signatory outside this pool
        // leaves the select on "-Select Signatory-", which is the blank field the seed used to show.
        $signatories = DB::table('ContractUsers')->orderBy('id')->limit(20)->pluck('id')->all();

        foreach (['accessibleBranches', 'otherBranches', 'departments', 'contractTypes', 'categories', 'entities', 'signatories'] as $name) {
            if (empty($$name)) {
                throw new \RuntimeException("Reference pool '{$name}' is empty; cannot build plausible rows.");
            }
        }

        return compact('accessibleBranches', 'otherBranches', 'departments', 'contractTypes', 'categories', 'entities', 'signatories');
    }

    // ---------------------------------------------------------------- contracts

    private function buildContracts(array $pools): array
    {
        $vendors = [
            'Sundaram Facilities', 'Kavin Medtech', 'Orion Diagnostics', 'Blue Ridge Logistics',
            'Aster Pharma Supply', 'Zenith Security Services', 'Nova Biomedical', 'Cauvery Catering',
            'Helix IT Solutions', 'Pinnacle Housekeeping', 'Everest Elevators', 'Meridian Radiology',
        ];
        // The four values the Duration radio group on the edit tab offers. 'autoRenewal' was
        // seeded here before and is not one of them, so no radio was ever checked. Automatic
        // renewal is a property of a fixedTerm contract (renewal_type), not an end type.
        $endTypes    = ['onetimeContract', 'fixedTerm', 'evergreen'];
        // The priority select offers low / medium / high only. 'critical' selected nothing.
        $priorities  = ['low', 'medium', 'high'];

        // Encrypt the small set of repeated values once instead of 3,000 times each.
        $encEndType  = array_map(fn ($v) => encryptString($v, 'end_contract_type'), array_combine($endTypes, $endTypes));
        $encCurrency = encryptString('INR', 'currency');
        $encMode     = encryptString('new', 'contract_mode');
        // The commencement radio posts 'FixedDate' or 'Eventbased', and the write path encrypts it.
        $encCommencement = encryptString('FixedDate', 'commencement_type');
        $encCurrencyContract = encryptString('INR', 'currency_contract');
        $encEvergreen = encryptString('mutually', 'evergreen_condition');
        $encTerminationReason = encryptString('mutually', 'termination_reason');

        // Every remaining pool below feeds a field the contract detail page reads. Each one is
        // encrypted once per distinct value, not once per row: 3,000 rows times 25 encrypted
        // columns is 75,000 AES calls otherwise.
        $pool = [
            'renewal_type'         => ['automaticrenewal', 'manualRenewal'],
            'period_auto_renewal_unit' => ['years', 'months'],
            'exclusivity'          => ['Exclusivity to Company', 'Exclusive to Contracting Party', 'Mutually Exclusive', 'Non Exclusive'],
            'billing_frequency'    => ['Weekly', 'Monthly', 'Quarterly', 'Half Yearly', 'Annually', 'Onetime'],
            'payment_schedule'     => [
                'Monthly in arrears, on the 5th',
                'Quarterly in advance',
                'On milestone sign-off',
                '50% advance, 50% on delivery',
                'Net 30 from the invoice date',
            ],
            'payment_terms'        => [
                'Net 30 days from receipt of a valid invoice. Late payment carries 1.5% per month.',
                'Net 45 days. Invoices without a purchase order number are returned unpaid.',
                'Payment within 15 days of milestone acceptance by the department head.',
                'Net 60 days. Any disputed amount is held back until the dispute closes.',
            ],
            'taxes'                => ['18% GST extra', '12% GST included', '5% GST plus TDS at 2%', 'GST as applicable', '18% GST, TDS 10% at source'],
            'escalation_clauses'   => ['5% every 12 months', 'CPI linked, reviewed each year', 'No escalation for the first 24 months', '7% on each renewal', 'Fixed for the full term'],
            'discounts'            => ['2% for payment within 10 days', 'Volume discount 5% above 1,000 units', 'No discount', '3% early settlement discount'],
            'retention'            => ['5% held until final acceptance', '10% held for 12 months', 'No retention', '2.5% held against defects'],
            'payment_escrow'       => ['Not applicable', 'Escrow released on milestone sign-off', 'Source code held in escrow'],
            'financial_guarantees' => ['Bank guarantee for 10% of the contract value', 'Performance bond for 12 months', 'Corporate guarantee from the parent company', 'None'],
            'currency_conversion'  => ['Not applicable, INR only', 'RBI reference rate on the invoice date', 'Spot rate on the payment date'],
            // 'on' / 'off' is what the reminder checkbox posts.
            'reminder_enable'      => ['on', 'on', 'on', 'off'],
            'reminder_alert'       => ['Contract End Date', 'Renewal Date'],
            'reminder_repeats'     => ['Daily', 'Every 3 days', 'Weekly', 'Fortnightly', 'Monthly', 'Never'],
            // "<number> <days|months|years> <prior|after>" - the shape the write path builds and
            // the shape reminder_alert_parts() splits back into three form fields.
            'alert_on_first'       => ['30 days prior', '45 days prior', '60 days prior', '15 days prior', '90 days prior', '3 months prior'],
            'alert_on_second'      => ['15 days prior', '7 days prior', '30 days prior', '21 days prior'],
            'alert_on_escalation'  => ['7 days prior', '5 days prior', '10 days prior', '14 days prior'],
            'alert_on_after'       => ['7 days after', '3 days after', '14 days after', '1 months after'],
        ];

        // 'reminder_alert', 'reminder_repeats' and the four alert_on_* pools feed several columns
        // each. decryptString() only needs the ciphertext, not a matching column name, so one
        // encryption per distinct string is enough.
        $enc = [];
        foreach ($pool as $name => $values) {
            foreach (array_unique($values) as $value) {
                $enc[$name][$value] = encryptString($value, $name);
            }
        }
        $pick = function (string $name, int $i) use ($pool, $enc): string {
            $value = $pool[$name][$i % count($pool[$name])];

            return $enc[$name][$value];
        };

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

            $name  = sprintf('%s Agreement - %s (Seed %s)', $endType === 'evergreen' ? 'Service' : 'Supply', $vendor, $seq);
            $value = 25000 + (($n * 4137) % 9750000);

            // Dates spread over a few years so expiry/renewal views have something to show.
            $start = strtotime('2022-01-01 +' . (($n * 7) % 1460) . ' days');
            $end   = strtotime('+' . (12 + ($n % 48)) . ' months', $start);

            // Automatic renewal belongs to a fixedTerm contract. Half of them renew
            // automatically with a notice period, half renew by hand.
            $isFixedTerm  = $endType === 'fixedTerm';
            $isAutoRenew  = $isFixedTerm && ($n % 2) === 0;
            $isTerminated = $substatus === 'Terminated';

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
                'contract_description' => encryptString(
                    'Synthetic row ' . $seq . ' from PerfDatasetSeeder. Supply and service scope for ' . $vendor . '.',
                    'contract_description'
                ),
                'contract_tags'        => json_encode([(string) $typeId]),
                'commencement_type'    => $encCommencement,
                'fixed_date'           => date('Y-m-d', $start),
                'contract_end_date'    => date('Y-m-d', $end),
                'end_contract_type'    => $encEndType[$endType],
                'onetime_end_date'     => $endType === 'onetimeContract' ? date('Y-m-d', $end) : null,
                'fixedterm_end_date'   => $isFixedTerm ? date('Y-m-d', $end) : null,
                'renewal_type'         => $isFixedTerm ? $pick('renewal_type', $isAutoRenew ? 0 : 1) : null,
                'period_auto_renewal'  => $isAutoRenew ? (1 + ($n % 3)) : null,
                'period_auto_renewal_unit' => $isAutoRenew ? $pick('period_auto_renewal_unit', $n) : null,
                'auto_renewal_date'    => $isAutoRenew ? date('Y-m-d', $end) : null,
                'manual_renewal_date'  => ($isFixedTerm && ! $isAutoRenew) ? date('Y-m-d', $end) : null,
                'evergreen_condition'  => $endType === 'evergreen' ? $encEvergreen : null,
                'termination_date'     => $isTerminated ? date('Y-m-d', $end) : null,
                'termination_reason'   => $isTerminated ? $encTerminationReason : null,
                'signing_date'         => in_array($status, ['Signing', 'Executed'], true) ? date('Y-m-d', $start) : null,
                'exclusivity'          => $pick('exclusivity', $n),
                'currency'             => $encCurrency,
                'currency_value'       => encryptString((string) $value, 'currency_value'),
                'billing_value'        => (string) $value,
                'total_value'          => (string) $value,
                'currency_contract'    => $encCurrencyContract,
                'billing_frequency'    => $pick('billing_frequency', $n),
                'payment_schedule'     => $pick('payment_schedule', $n),
                'payment_terms'        => $pick('payment_terms', $n),
                'taxes'                => $pick('taxes', $n),
                'escalation_clauses'   => $pick('escalation_clauses', $n),
                'discounts'            => $pick('discounts', $n),
                'retention'            => $pick('retention', $n),
                'payment_escrow'       => $pick('payment_escrow', $n),
                'financial_guarantees' => $pick('financial_guarantees', $n),
                'currency_conversion'  => $pick('currency_conversion', $n),

                // Four blade blocks split these on a space and read index 1 and 2. A NULL here
                // is what crashed the page (ticket 01), so all fifteen get a value.
                'reminder_enable'                        => $pick('reminder_enable', $n),
                'reminder_first_alert'                   => $pick('reminder_alert', $n),
                'reminder_first_alertMeOn'               => $pick('alert_on_first', $n),
                'reminder_first_alert_repeats'           => $pick('reminder_repeats', $n),
                'reminder_second_alert'                  => $pick('reminder_alert', $n + 1),
                'reminder_second_alertMeOn'              => $pick('alert_on_second', $n),
                'reminder_second_alert_repeats'          => $pick('reminder_repeats', $n + 2),
                'reminder_escalation_alert'              => $pick('reminder_alert', $n),
                'reminder_escalation_alertMeOn'          => $pick('alert_on_escalation', $n),
                'reminder_escalation_alert_repeats'      => $pick('reminder_repeats', $n + 4),
                'reminder_escalation_alert_after'        => $pick('reminder_alert', $n + 1),
                'reminder_escalation_alertMeOn_after'    => $pick('alert_on_after', $n),
                'reminder_escalation_alert_repeats_after' => $pick('reminder_repeats', $n + 1),

                // Read at 25 sites across seven partials. The path is a Google Drive style file
                // id, which is what the real rows hold; Storage::exists() says no and the page
                // links to /invalidfile, the same as a real contract whose file has gone.
                'contract_attachment'          => self::MARKER . str_pad((string) $id, 12, '0', STR_PAD_LEFT),
                'contract_attachment_filename' => 'contract_' . $id . '_seedperf.docx',

                'catgoery_id'          => (string) $pools['categories'][$n % count($pools['categories'])],
                'department_id'        => $deptId,
                'owner'                => 1507,
                'created_by'           => 1507,
                'signatory'            => $pools['signatories'][$n % count($pools['signatories'])],
                // Drives the whole approval-flow render in contractFlow / contractApprovalsView /
                // signApprovals. NULL made every one of those blocks draw nothing.
                'rules_id'             => $this->buildRulesId($n),
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
                'contract_priority'    => $priorities[$n % count($priorities)],
                'contract_name_hash'   => hash('sha256', $name),
                'tenure'               => (12 + ($n % 48)) . ' months',
                'created_at'           => date('Y-m-d H:i:s', $start),
                'updated_at'           => date('Y-m-d H:i:s', $start),
            ];
        }

        return [$rows, $meta];
    }

    /**
     * The approval-rule payload contracts.rules_id holds: a JSON array of one object whose
     * 'approver' and 'signatory' members are themselves JSON strings. Shape copied from the
     * real rows; contractFlow.blade.php walks it to draw the approval flow, and
     * contractApprovalsView / signApprovals read it too.
     *
     * Four variants, picked by $n, so the flow is not identical on every page: sequential or
     * parallel review, and with or without a signatory group.
     */
    private function buildRulesId(int $n): string
    {
        static $cache = [];

        $variant = $n % 4;

        if (isset($cache[$variant])) {
            return $cache[$variant];
        }

        $approver = function (string $type, array $people): array {
            return [[
                'role'                     => 'Approver',
                'approval_type'            => $type,
                'auto_next_enabled'        => 0,
                'dynamic_approver_enabled' => 0,
                'approvers'                => $people,
            ]];
        };

        $jeeva     = ['id' => 1507, 'type' => 'name', 'name' => 'Jeeva', 'email' => 'jeevanantham@legalitysimplified.com'];
        $ownerOne  = ['id' => 1508, 'type' => 'name', 'name' => 'Owner One', 'email' => 'owner.one@example.com'];
        $apprOne   = ['id' => 1509, 'type' => 'name', 'name' => 'Approver One', 'email' => 'approver.one@example.com'];
        $signOne   = ['id' => 1510, 'type' => 'name', 'name' => 'Signatory One', 'email' => 'signatory.one@example.com'];

        $reviewType = ($variant % 2) === 0 ? 'sequential' : 'parallel';
        $reviewers  = ($variant % 2) === 0 ? [$jeeva] : [$jeeva, $ownerOne];

        $approverPayload = [
            'review'          => $approver($reviewType, $reviewers),
            'negotiation'     => [],
            'finalization'    => $approver('sequential', [$apprOne]),
            'approval'        => $approver('sequential', [$ownerOne]),
            'signatory'       => $variant < 2 ? $approver('sequential', [$signOne]) : [],
            '_parent_routing' => [
                'review'       => ['on_approve' => 'negotiation', 'on_reject' => ''],
                'negotiation'  => ['on_approve' => 'finalization', 'on_reject' => 'review'],
                'finalization' => ['on_approve' => 'approval', 'on_reject' => 'negotiation'],
                'approval'     => ['on_approve' => 'signatory', 'on_reject' => 'review'],
            ],
        ];

        $cache[$variant] = json_encode([[
            'id'              => 1,
            'approval_type'   => 'sequential',
            'approval_status' => 'required',
            'approver'        => json_encode($approverPayload),
            'signatory'       => json_encode(['sign' => '228', 'owner' => '1507', 'notify' => [], 'signutform' => []]),
        ]]);

        return $cache[$variant];
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
        // approval_contracts.approval_status is a plain varchar(20) since migration
        // 2026_08_21_000001_narrow_approval_contracts_approval_status. encryptStringx() reads
        // config('app.PLAINTEXT_COLUMNS') and returns the word unchanged, which is what every
        // write site in the app now does. encryptString() here overran the column.
        $encApprovalStatus = [];
        foreach ($approvalStatuses as $s) {
            $encApprovalStatus[$s] = encryptStringx($s, 'approval_contracts.approval_status');
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

    private function insertChunked(string $table, array $rows, ?int $size = null): void
    {
        foreach (array_chunk($rows, $size ?? self::CHUNK) as $chunk) {
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
