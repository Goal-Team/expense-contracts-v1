<?php

namespace Modules\Contract\Services;

use App\Models\AddUsers;
use App\Models\ApprovalContracts;
use App\Models\BranchUser;
use App\Models\ContractPartyData;
use App\Models\ContractType;
use App\Models\EntityBusiness;
use App\Models\FinancialLimit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Contract\Http\Controllers\ContractController;

class ApprovalEntriesBackfillService
{
    public function getMissingExecutedContractIds(): array
    {
        return DB::table('contracts as c')
            ->leftJoin('approval_contracts as ac', 'ac.contract_id', '=', 'c.id')
            ->whereRaw("LOWER(TRIM(COALESCE(c.contract_status, ''))) = ?", ['executed'])
            ->where('c.status', 1)
            ->whereNull('ac.id')
            ->orderBy('c.id', 'desc')
            ->pluck('c.id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->filter(function ($id) {
                return $id > 0;
            })
            ->values()
            ->all();
    }

    public function getMissingExecutedContracts(array $filters = []): Collection
    {
        $locationId = (int) ($filters['location_id'] ?? 0);

        $query = DB::table('contracts as c')
            ->leftJoin('approval_contracts as ac', 'ac.contract_id', '=', 'c.id')
            ->select(
                'c.id',
                'c.contract_unique_id',
                'c.contract_type',
                'c.currency',
                'c.currency_value',
                'c.owner',
                'c.contract_status',
                'c.substatus',
                'c.status'
            )
            ->whereRaw("LOWER(TRIM(COALESCE(c.contract_status, ''))) = ?", ['executed'])
            ->where('c.status', 1)
            ->whereNull('ac.id');

        if ($locationId > 0) {
            $query->whereExists(function ($sub) use ($locationId) {
                $sub->select(DB::raw(1))
                    ->from('contract_party_data as cpd')
                    ->whereColumn('cpd.custom_field_group_id', 'c.id')
                    ->where('cpd.contract_party_type', 'Internal')
                    ->where('cpd.contract_party_location_id', $locationId);
            });
        }

        $rows = $query
            ->orderBy('c.id', 'desc')
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $contractTypeIds = $rows->pluck('contract_type')->filter()->unique()->values();
        $contractTypes = ContractType::whereIn('contract_type_id', $contractTypeIds)
            ->get(['contract_type_id', 'contract_type'])
            ->keyBy('contract_type_id');

        $contractIds = $rows->pluck('id')->all();
        $locationMap = $this->buildLocationMap($contractIds);

        return $rows->map(function ($row, $index) use ($contractTypes, $locationMap) {
            $typeName = data_get($contractTypes->get($row->contract_type), 'contract_type', '-');
            $value = $this->safeDecrypt($row->currency_value ?? null, 'currency_value');
            $currency = $this->safeDecrypt($row->currency ?? null, 'currency');

            return [
                's_no' => $index + 1,
                'id' => (int) $row->id,
                'contract_id_display' => $row->contract_unique_id ?: (string) $row->id,
                'contract_type' => $typeName ?: '-',
                'value' => $this->formatValue($value, $currency),
                'location' => $locationMap[$row->id] ?? '-',
                'approval_entries' => 'No entries',
            ];
        });
    }

    public function insertForContract(int $contractId, int $actorId, bool $skipApprovalFlow = true): array
    {
        return DB::transaction(function () use ($contractId, $actorId, $skipApprovalFlow) {
            $contract = DB::table('contracts')
                ->where('id', $contractId)
                ->lockForUpdate()
                ->first();

            if (! $contract) {
                return [
                    'inserted' => 0,
                    'skipped' => 0,
                    'failed' => 1,
                    'errors' => ["Contract {$contractId} not found"],
                ];
            }

            $isExecuted = strtolower(trim((string) ($contract->contract_status ?? ''))) === 'executed';
            if (! $isExecuted || (int) ($contract->status ?? 0) !== 1) {
                return [
                    'inserted' => 0,
                    'skipped' => 1,
                    'failed' => 0,
                    'errors' => ["Contract {$contractId} is not an active executed contract"],
                ];
            }

            $rulesId = $this->computeRulesIdForContract($contract);
            if (! $rulesId) {
                return [
                    'inserted' => 0,
                    'skipped' => 1,
                    'failed' => 0,
                    'errors' => ["Contract {$contractId}: rules_id could not be computed"],
                ];
            }

            DB::table('contracts')
                ->where('id', $contractId)
                ->update(['rules_id' => $rulesId]);

            if ($skipApprovalFlow) {
                return [
                    'inserted' => 1,
                    'skipped' => 0,
                    'failed' => 0,
                    'errors' => [],
                ];
            }

            $existing = ApprovalContracts::where('contract_id', $contractId)->exists();
            if ($existing) {
                return [
                    'inserted' => 1,
                    'skipped' => 0,
                    'failed' => 0,
                    'errors' => [],
                ];
            }

            $locationId = $this->getInternalLocationId($contractId);
            if (! $locationId) {
                return [
                    'inserted' => 1,
                    'skipped' => 0,
                    'failed' => 0,
                    'errors' => ["Contract {$contractId}: no internal location found"],
                ];
            }

            $contractValue = $this->safeDecrypt($contract->currency_value ?? null, 'currency_value');
            $approvalUserColumn = 'approval_required_users';
            $financialLimit = app(ContractController::class)->financialLimit(
                $locationId,
                $contract->department_id ?? null,
                $contract->catgoery_id ?? null,
                $contract->contract_type ?? null,
                $contractValue,
                $approvalUserColumn
            );

            $limitRow = $this->extractFinancialLimitRow($financialLimit);
            if (! $limitRow) {
                return [
                    'inserted' => 1,
                    'skipped' => 0,
                    'failed' => 0,
                    'errors' => ["Contract {$contractId}: financial limit not resolved"],
                ];
            }

            $approvalType = strtolower((string) $this->extractGlobalRuleValue(data_get($limitRow, 'approval_type'), 'sequential'));
            if ($approvalType === '') {
                $approvalType = 'sequential';
            }

            $resolvedApproverRows = $this->resolveApproverRows(
                data_get($limitRow, 'approver'),
                $locationId,
                (int) ($contract->department_id ?? 0),
                $approvalType
            );

            if (empty($resolvedApproverRows)) {
                return [
                    'inserted' => 1,
                    'skipped' => 0,
                    'failed' => 0,
                    'errors' => ["Contract {$contractId}: no approvers resolved from financial limit"],
                ];
            }

            $approverUsers = AddUsers::select('id', decrypt_data('Email', 'AddUsers'), decrypt_data('FirstName', 'AddUsers'))
                ->whereIn('id', array_values(array_unique(array_column($resolvedApproverRows, 'approver_id'))))
                ->get()
                ->keyBy('id');

            $baseUniqueId = $contractId . '_backfill_' . substr((string) microtime(true), -6);
            $createdRows = 0;
            $orderval = 1;

            foreach ($resolvedApproverRows as $row) {
                if ((int) $row['approver_id'] === (int) ($contract->owner ?? 0) || strtolower((string) $row['approver_type_row']) === 'owner') {
                    continue;
                }

                $user = $approverUsers->get((int) $row['approver_id']);
                if (! $user || ! data_get($user, 'Email')) {
                    continue;
                }

                $usernamePayload = json_encode([
                    'email' => data_get($user, 'Email'),
                    'name' => data_get($user, 'FirstName'),
                ]);

                ApprovalContracts::create(ApprovalContracts::prepareData([
                    'username' => $this->safeEncrypt($usernamePayload, 'username'),
                    'unique_id' => $baseUniqueId . '_g' . $row['group_index'],
                    'status' => $this->safeEncrypt('0', 'status'),
                    'orderval' => $orderval,
                    'previous_status' => $this->safeEncrypt((string) ($contract->contract_status ?? 'executed'), 'previous_status'),
                    'contract_id' => $contractId,
                    'next_action_item' => null,
                    'next_action_description' => null,
                    'button_text' => 'Open',
                    'attachments' => $contract->contract_attachment ?? null,
                    'attachments_filename' => $contract->contract_attachment_filename ?? null,
                    'approval_status' => $this->safeEncrypt('approved', 'approval_status'),
                    'flag' => 0,
                    'next_status' => $this->safeEncrypt((string) ($contract->contract_status ?? 'executed'), 'next_status'),
                    'created_by' => json_encode(['actor_id' => $actorId, 'context' => 'approval_entries_backfill']),
                    'approval_type_main' => $approvalType,
                    'approval_type_row' => $row['approval_type_row'],
                    'approver_type_row' => $row['approver_type_row'],
                    'dynamic_approver_enabled' => $row['dynamic_approver_enabled'],
                ]));

                $orderval++;
                $createdRows++;
            }

            if ($createdRows < 1) {
                return [
                    'inserted' => 1,
                    'skipped' => 0,
                    'failed' => 0,
                    'errors' => ["Contract {$contractId}: approvers resolved but users were not found"],
                ];
            }

            Log::info('Approval entries backfill created from financial limit', [
                'contract_id' => $contractId,
                'rows_created' => $createdRows,
                'approval_type' => $approvalType,
                'location_id' => $locationId,
            ]);

            return [
                'inserted' => 1,
                'skipped' => 0,
                'failed' => 0,
                'errors' => [],
            ];
        });
    }

    public function buildPreviewForContracts(array $contractIds, int $actorId): array
    {
        $uniqueIds = array_values(array_unique(array_filter(array_map('intval', $contractIds), function ($id) {
            return $id > 0;
        })));

        $items = [];
        $errors = [];

        foreach ($uniqueIds as $contractId) {
            try {
                $items[] = $this->buildPreviewItemForContract($contractId);
            } catch (\Throwable $e) {
                $items[] = [
                    'contract_id' => $contractId,
                    'status' => 'failed',
                    'message' => 'Preview failed',
                    'warnings' => [],
                    'errors' => ["Contract {$contractId}: " . $e->getMessage()],
                ];
                $errors[] = "Contract {$contractId}: " . $e->getMessage();
            }
        }

        $ruleMap = [];
        foreach ($items as $item) {
            $ruleMap[(int) ($item['contract_id'] ?? 0)] = (int) (($item['selected_rule']['id'] ?? 0));
        }

        return [
            'items' => $items,
            'errors' => $errors,
            'preview_token' => $this->generatePreviewToken($ruleMap, $actorId),
            'summary' => [
                'total' => count($items),
                'ok' => count(array_filter($items, function ($item) {
                    return ($item['status'] ?? '') === 'ok';
                })),
                'warnings' => count(array_filter($items, function ($item) {
                    return ! empty($item['warnings']);
                })),
                'failed' => count(array_filter($items, function ($item) {
                    return ($item['status'] ?? '') === 'failed';
                })),
            ],
        ];
    }

    public function validatePreviewTokenForContracts(string $token, array $contractIds, int $actorId): array
    {
        $parsed = $this->parsePreviewToken($token);
        if (! $parsed['ok']) {
            return $parsed;
        }

        $payload = $parsed['payload'];
        $tokenActorId = (int) ($payload['actor_id'] ?? 0);
        if ($tokenActorId !== $actorId) {
            return [
                'ok' => false,
                'message' => 'Preview token does not belong to current user.',
            ];
        }

        $tokenRuleMap = [];
        foreach ((array) ($payload['rules'] ?? []) as $contractId => $ruleId) {
            $tokenRuleMap[(int) $contractId] = (int) $ruleId;
        }

        $requestedIds = array_values(array_unique(array_filter(array_map('intval', $contractIds), function ($id) {
            return $id > 0;
        })));

        if (empty($requestedIds)) {
            return [
                'ok' => false,
                'message' => 'Backfill inputs changed after preview. Please preview again before insert.',
            ];
        }

        foreach ($requestedIds as $requestedId) {
            if (! array_key_exists($requestedId, $tokenRuleMap)) {
                return [
                    'ok' => false,
                    'message' => 'Backfill inputs changed after preview. Please preview again before insert.',
                ];
            }
        }

        $currentPreviews = $this->buildPreviewForContracts($requestedIds, $actorId);
        $currentRuleMap = [];
        foreach ((array) ($currentPreviews['items'] ?? []) as $item) {
            $currentRuleMap[(int) ($item['contract_id'] ?? 0)] = (int) (($item['selected_rule']['id'] ?? 0));
        }

        foreach ($requestedIds as $requestedId) {
            $currentRule = (int) ($currentRuleMap[$requestedId] ?? 0);
            $tokenRule = (int) ($tokenRuleMap[$requestedId] ?? 0);
            if ($currentRule !== $tokenRule) {
                return [
                    'ok' => false,
                    'message' => 'Backfill inputs changed after preview. Please preview again before insert.',
                ];
            }
        }

        return [
            'ok' => true,
            'message' => 'Preview token is valid.',
        ];
    }

    public function getMissingExecutedLocationOptions(): array
    {
        $contractIds = $this->getMissingExecutedContractIds();
        if (empty($contractIds)) {
            return [];
        }

        $locationIds = ContractPartyData::query()
            ->whereIn('custom_field_group_id', $contractIds)
            ->where('contract_party_type', 'Internal')
            ->whereNotNull('contract_party_location_id')
            ->pluck('contract_party_location_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->filter(function ($id) {
                return $id > 0;
            })
            ->unique()
            ->values()
            ->all();

        if (empty($locationIds)) {
            return [];
        }

        return BranchUser::query()
            ->select('id', decrypt_data('BranchName', 'branch'))
            ->whereIn('id', $locationIds)
            ->orderBy('BranchName')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'name' => (string) ($row->BranchName ?? ('Location #' . $row->id)),
                ];
            })
            ->values()
            ->all();
    }

    private function buildPreviewItemForContract(int $contractId): array
    {
        $contract = DB::table('contracts')->where('id', $contractId)->first();
        if (! $contract) {
            return [
                'contract_id' => $contractId,
                'status' => 'failed',
                'message' => 'Contract not found',
                'selected_rule' => ['id' => 0],
                'used_values' => [],
                'evaluated_groups' => [],
                'warnings' => [],
                'errors' => ["Contract {$contractId} not found"],
            ];
        }

        $existing = ApprovalContracts::where('contract_id', $contractId)->exists();
        $locationId = $this->getInternalLocationId($contractId);
        $contractValue = $this->safeDecrypt($contract->currency_value ?? null, 'currency_value');
        $approvalColumn = 'approval_required_users';

        $usedValues = [
            'location' => $locationId,
            'department' => $contract->department_id ?? null,
            'category' => $contract->catgoery_id ?? null,
            'contract_type' => $contract->contract_type ?? null,
            'contract_value' => $contractValue,
            'approval_column' => $approvalColumn,
            'contract_status' => $contract->contract_status ?? null,
            'has_existing_entries' => $existing,
        ];

        if (! $locationId) {
            return [
                'contract_id' => $contractId,
                'contract_id_display' => $contract->contract_unique_id ?: (string) $contractId,
                'status' => 'failed',
                'message' => 'No internal location found',
                'selected_rule' => ['id' => 0],
                'used_values' => $usedValues,
                'evaluated_groups' => [],
                'warnings' => [],
                'errors' => ["Contract {$contractId}: no internal location found"],
            ];
        }

        $evaluation = $this->evaluateFinancialLimitWithExplanation(
            $locationId,
            $contract->department_id ?? null,
            $contract->catgoery_id ?? null,
            $contract->contract_type ?? null,
            $contractValue,
            $approvalColumn
        );

        $warnings = (array) ($evaluation['warnings'] ?? []);
        if ($existing) {
            $warnings[] = 'Approval entries already exist for this contract; insert may be skipped.';
        }

        return [
            'contract_id' => $contractId,
            'contract_id_display' => $contract->contract_unique_id ?: (string) $contractId,
            'status' => 'ok',
            'message' => 'Preview generated',
            'selected_rule' => [
                'id' => (int) (($evaluation['selected_rule']['id'] ?? 0)),
                'approval_name' => $evaluation['selected_rule']['approval_name'] ?? '-',
                'approval_type' => $evaluation['selected_rule']['approval_type'] ?? '-',
                'approval_status' => $evaluation['selected_rule']['approval_status'] ?? '-',
                'is_default_fallback' => (bool) ($evaluation['selected_rule']['is_default_fallback'] ?? false),
            ],
            'used_values' => $usedValues,
            'evaluated_groups' => (array) ($evaluation['evaluated_groups'] ?? []),
            'warnings' => array_values(array_unique(array_filter($warnings))),
            'errors' => [],
        ];
    }

    private function evaluateFinancialLimitWithExplanation($location, $department, $category, $contractType, $contractValue, string $approvalColumn): array
    {
        $warnings = [];
        $evaluatedGroups = [];
        $selectedRule = [
            'id' => 0,
            'approval_name' => '-',
            'approval_type' => '-',
            'approval_status' => '-',
            'is_default_fallback' => true,
        ];

        $defaultLimit = FinancialLimit::select('id', 'approval_name', 'approval_type', 'approval_status')
            ->where('id', 1)
            ->first();

        $contractValueNum = null;
        if ($contractValue !== null && $contractValue !== '' && strtoupper((string) $contractValue) !== 'NULL') {
            $contractValueNum = is_numeric($contractValue) ? (float) $contractValue : null;
            if ($contractValueNum === null) {
                $warnings[] = 'Contract value is not numeric; range-based rules may not match.';
            }
        }

        try {
            $limits = FinancialLimit::where('status', 1)
                ->where('approval_flow_type', 'normal')
                ->whereNotNull('rule_builder_data')
                ->where('id', '<>' , 1)
                ->get();

            foreach ($limits as $limit) {
                $rb = @json_decode($limit->rule_builder_data, true);
                if (! is_array($rb) || empty($rb['gcondition']) || ! is_array($rb['gcondition'])) {
                    $warnings[] = "Financial limit {$limit->id} skipped: malformed rule_builder_data.";
                    continue;
                }

                foreach ($rb['gcondition'] as $groupIndex => $group) {
                    $gLocations = $group['location'] ?? ($group['locations'] ?? null);
                    $gDepartments = $group['department'] ?? ($group['departments'] ?? null);
                    $gCategory = $group['category'] ?? ($group['categories'] ?? null);
                    $gContractTypes = $group['contractType'] ?? ($group['contract_type'] ?? null);

                    $toArray = function ($v) {
                        if (is_null($v) || $v === '') {
                            return [];
                        }
                        if (is_array($v)) {
                            return array_values($v);
                        }
                        return [$v];
                    };

                    $locs = array_map('strval', $toArray($gLocations));
                    $depts = array_map('strval', $toArray($gDepartments));
                    $cats = array_map('strval', $toArray($gCategory));
                    $cts = array_map('strval', $toArray($gContractTypes));

                    $matches = function ($arr, $val) {
                        if (empty($arr)) {
                            return true;
                        }
                        if (in_array('0', $arr, true) || in_array(0, $arr, true)) {
                            return true;
                        }
                        return in_array((string) $val, $arr, true) || in_array((int) $val, $arr, true);
                    };

                    $locationMatch = $matches($locs, $location);
                    $departmentMatch = $matches($depts, $department);
                    $categoryMatch = $matches($cats, $category);
                    $contractTypeMatch = $matches($cts, $contractType);

                    $min = isset($group['limitFrom']) && $group['limitFrom'] !== '' ? (float) $group['limitFrom'] : null;
                    $max = isset($group['limitUp']) && $group['limitUp'] !== '' ? (float) $group['limitUp'] : null;

                    $valueMatch = true;
                    $failedReasons = [];

                    if (! $locationMatch) {
                        $failedReasons[] = 'Location mismatch';
                    }
                    if (! $departmentMatch) {
                        $failedReasons[] = 'Department mismatch';
                    }
                    if (! $categoryMatch) {
                        $failedReasons[] = 'Category mismatch';
                    }
                    if (! $contractTypeMatch) {
                        $failedReasons[] = 'Contract type mismatch';
                    }

                    if ($min === null && $max === null) {
                        $valueMatch = true;
                    } elseif ($contractValueNum === null) {
                        $valueMatch = false;
                        $failedReasons[] = 'Contract value missing/non-numeric for range rule';
                    } else {
                        $valueMatch = ($min === null || $contractValueNum >= $min) && ($max === null || $contractValueNum <= $max);
                        if (! $valueMatch) {
                            $failedReasons[] = 'Contract value out of range';
                        }
                    }

                    $overallMatch = $locationMatch && $departmentMatch && $categoryMatch && $contractTypeMatch && $valueMatch;

                    $evaluatedGroups[] = [
                        'financial_limit_id' => (int) $limit->id,
                        'group_index' => (int) $groupIndex,
                        'group_conditions' => [
                            'location' => $locs,
                            'department' => $depts,
                            'category' => $cats,
                            'contract_type' => $cts,
                            'limitFrom' => $min,
                            'limitUp' => $max,
                        ],
                        'match_result' => [
                            'location_match' => $locationMatch,
                            'department_match' => $departmentMatch,
                            'category_match' => $categoryMatch,
                            'contract_type_match' => $contractTypeMatch,
                            'value_match' => $valueMatch,
                        ],
                        'overall_match' => $overallMatch,
                        'failed_reasons' => $failedReasons,
                    ];

                    if ($overallMatch) {
                        $approvalType = $this->extractGlobalRuleValue($limit->approval_type, '-');
                        $approvalStatus = $this->extractGlobalRuleValue($limit->approval_status, '-');
                        $selectedRule = [
                            'id' => (int) $limit->id,
                            'approval_name' => (string) ($limit->approval_name ?? '-'),
                            'approval_type' => is_scalar($approvalType) ? (string) $approvalType : '-',
                            'approval_status' => is_scalar($approvalStatus) ? (string) $approvalStatus : '-',
                            'is_default_fallback' => false,
                        ];

                        return [
                            'selected_rule' => $selectedRule,
                            'evaluated_groups' => $evaluatedGroups,
                            'warnings' => array_values(array_unique(array_filter($warnings))),
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            $warnings[] = 'Error while parsing financial limit rules; fallback to default rule.';
            Log::error('backfill preview financial limit parse error', ['error' => $e->getMessage()]);
        }

        if ($defaultLimit) {
            $approvalType = $this->extractGlobalRuleValue($defaultLimit->approval_type, '-');
            $approvalStatus = $this->extractGlobalRuleValue($defaultLimit->approval_status, '-');
            $selectedRule = [
                'id' => (int) $defaultLimit->id,
                'approval_name' => (string) ($defaultLimit->approval_name ?? '-'),
                'approval_type' => is_scalar($approvalType) ? (string) $approvalType : '-',
                'approval_status' => is_scalar($approvalStatus) ? (string) $approvalStatus : '-',
                'is_default_fallback' => true,
            ];
            $warnings[] = 'No exact DOA match. Default rule applied.';
        } else {
            $warnings[] = 'No exact DOA match and default rule not found.';
        }

        return [
            'selected_rule' => $selectedRule,
            'evaluated_groups' => $evaluatedGroups,
            'warnings' => array_values(array_unique(array_filter($warnings))),
        ];
    }

    private function generatePreviewToken(array $ruleMap, int $actorId): string
    {
        ksort($ruleMap);
        $payload = [
            'actor_id' => (int) $actorId,
            'issued_at' => time(),
            'rules' => $ruleMap,
        ];

        $json = json_encode($payload);
        $encoded = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $encoded, $this->previewTokenSecret());

        return $encoded . '.' . $signature;
    }

    private function parsePreviewToken(string $token): array
    {
        if (! is_string($token) || trim($token) === '' || strpos($token, '.') === false) {
            return [
                'ok' => false,
                'message' => 'Preview token is missing. Please preview before insert.',
            ];
        }

        [$encoded, $signature] = explode('.', $token, 2);
        if ($encoded === '' || $signature === '') {
            return [
                'ok' => false,
                'message' => 'Preview token is invalid. Please preview again.',
            ];
        }

        $expected = hash_hmac('sha256', $encoded, $this->previewTokenSecret());
        if (! hash_equals($expected, $signature)) {
            return [
                'ok' => false,
                'message' => 'Preview token signature mismatch. Please preview again.',
            ];
        }

        $decoded = base64_decode(strtr($encoded, '-_', '+/'));
        $payload = json_decode((string) $decoded, true);
        if (! is_array($payload) || ! isset($payload['rules']) || ! is_array($payload['rules'])) {
            return [
                'ok' => false,
                'message' => 'Preview token payload is invalid. Please preview again.',
            ];
        }

        return [
            'ok' => true,
            'payload' => $payload,
        ];
    }

    private function previewTokenSecret(): string
    {
        return (string) config('app.key', 'backfill-preview-secret');
    }

    private function getInternalLocationId(int $contractId): ?int
    {
        $row = ContractPartyData::select('contract_party_location_id')
            ->where('custom_field_group_id', $contractId)
            ->where('contract_party_type', 'Internal')
            ->whereNotNull('contract_party_location_id')
            ->orderBy('id')
            ->first();

        return $row ? (int) $row->contract_party_location_id : null;
    }

    protected function computeRulesIdForContract($contract): ?string
    {
        $locationId = $this->getInternalLocationId($contract->id);
        if (! $locationId) {
            return null;
        }

        $contractValue = $this->safeDecrypt($contract->currency_value ?? null, 'currency_value');
        $approvalUserColumn = 'approval_required_users';
        $financialLimit = app(ContractController::class)->financialLimit(
            $locationId,
            $contract->department_id ?? null,
            $contract->catgoery_id ?? null,
            $contract->contract_type ?? null,
            $contractValue,
            $approvalUserColumn
        );

        $payload = $this->normalizeRulesIdPayload($financialLimit);
        if (! is_array($payload)) {
            return null;
        }

        return json_encode([$payload]);
    }

    protected function normalizeRulesIdPayload($financialLimit): ?array
    {
        if (is_string($financialLimit)) {
            $decoded = json_decode($financialLimit, true);
        } elseif ($financialLimit instanceof Collection) {
            $decoded = $financialLimit->toArray();
        } elseif (is_array($financialLimit)) {
            $decoded = $financialLimit;
        } else {
            $decoded = json_decode(json_encode($financialLimit), true);
        }

        if (! is_array($decoded) || empty($decoded[0]) || ! is_array($decoded[0])) {
            return null;
        }

        $payload = $decoded[0];
        $approvalTypeGlobal = '0';

        $signatoryData = $this->decodeJsonAssoc($payload['signatory'] ?? null);
        if (is_array($signatoryData)) {
            $sign = $this->decodeJsonAssoc($signatoryData['sign'] ?? null);
            $owner = $this->decodeJsonAssoc($signatoryData['owner'] ?? null);
            $notify = $this->decodeJsonAssoc($signatoryData['notify'] ?? null);
            $signutform = $this->decodeJsonAssoc($signatoryData['signutform'] ?? null);

            $payload['signatory'] = json_encode([
                'sign' => is_array($sign) ? ($sign[$approvalTypeGlobal] ?? $sign[0] ?? null) : $sign,
                'owner' => is_array($owner) ? ($owner[$approvalTypeGlobal] ?? $owner[0] ?? null) : $owner,
                'notify' => is_array($notify) ? ($notify[$approvalTypeGlobal] ?? $notify[0] ?? []) : ($notify ?? []),
                'signutform' => is_array($signutform) ? ($signutform[$approvalTypeGlobal] ?? $signutform[0] ?? null) : $signutform,
            ]);
        }

        $approvalType = $this->decodeJsonAssoc($payload['approval_type'] ?? null);
        $payload['approval_type'] = is_array($approvalType) ? ($approvalType[$approvalTypeGlobal] ?? $approvalType[0] ?? $payload['approval_type'] ?? null) : $approvalType;

        $approvalStatus = $this->decodeJsonAssoc($payload['approval_status'] ?? null);
        $payload['approval_status'] = is_array($approvalStatus) ? ($approvalStatus[$approvalTypeGlobal] ?? $approvalStatus[0] ?? $payload['approval_status'] ?? null) : $approvalStatus;

        if (is_array($payload['approver'] ?? null) || is_object($payload['approver'] ?? null)) {
            $payload['approver'] = json_encode($payload['approver']);
        }

        return $payload;
    }

    private function extractFinancialLimitRow($financialLimit)
    {
        if ($financialLimit instanceof Collection) {
            return $financialLimit->first();
        }

        if (is_array($financialLimit)) {
            return $financialLimit[0] ?? null;
        }

        if (is_string($financialLimit)) {
            $decoded = json_decode($financialLimit);
            if (is_array($decoded)) {
                return $decoded[0] ?? null;
            }
        }

        return is_object($financialLimit) ? $financialLimit : null;
    }

    private function extractGlobalRuleValue($value, $default = null)
    {
        $decoded = $this->decodeJsonAssoc($value);

        if (is_array($decoded) && array_key_exists('0', $decoded)) {
            return $decoded['0'];
        }

        if (is_array($decoded) && array_key_exists(0, $decoded)) {
            return $decoded[0];
        }

        return $decoded === null ? $default : $decoded;
    }

    private function resolveApproverRows($approverValue, int $locationId, int $departmentId, string $defaultApprovalType): array
    {
        $decoded = $this->decodeJsonAssoc($approverValue);
        if (is_array($decoded) && array_key_exists('0', $decoded)) {
            //$decoded = $decoded['0'];
        }

        if (is_string($decoded)) {
            $decoded = $this->decodeJsonAssoc($decoded);
        }

        if (! is_array($decoded)) {
            return [];
        }

        $rows = [];
        $isGrouped = isset($decoded[0]) && is_array($decoded[0]) && array_key_exists('approvers', $decoded[0]);

        if ($isGrouped) {
            $groupIndex = 1;
            foreach ($decoded as $group) {
                if (! is_array($group)) {
                    continue;
                }

                $groupType = strtolower((string) ($group['approval_type'] ?? $defaultApprovalType));
                if ($groupType === '') {
                    $groupType = 'sequential';
                }

                $groupRole = (string) ($group['role'] ?? 'Approver');
                if ($groupRole === '') {
                    $groupRole = 'Approver';
                }

                $dynamicApproverEnabled = (int) (($group['dynamic_approver_enabled'] ?? 0) == 1 ? 1 : 0);
                $approvers = $group['approvers'] ?? [];
                if (is_string($approvers)) {
                    $approvers = $this->decodeJsonAssoc($approvers);
                }
                if (! is_array($approvers)) {
                    $approvers = [];
                }

                foreach ($approvers as $approver) {
                    $approverId = $this->resolveApproverId($approver, $locationId, $departmentId);
                    if (! $approverId) {
                        continue;
                    }

                    $rows[] = [
                        'approver_id' => $approverId,
                        'group_index' => $groupIndex,
                        'approval_type_row' => $groupType,
                        'approver_type_row' => $groupRole,
                        'dynamic_approver_enabled' => $dynamicApproverEnabled,
                    ];
                }

                $groupIndex++;
            }

            return $rows;
        }

        foreach ($decoded as $approver) {
            $approverId = $this->resolveApproverId($approver, $locationId, $departmentId);
            if (! $approverId) {
                continue;
            }

            $rows[] = [
                'approver_id' => $approverId,
                'group_index' => 1,
                'approval_type_row' => $defaultApprovalType ?: 'sequential',
                'approver_type_row' => 'Approver',
                'dynamic_approver_enabled' => 0,
            ];
        }

        return $rows;
    }

    private function resolveApproverId($approver, int $locationId, int $departmentId): ?int
    {
        if (is_numeric($approver)) {
            return (int) $approver;
        }

        if (is_object($approver)) {
            $approver = (array) $approver;
        }

        if (! is_array($approver)) {
            return null;
        }

        $id = $approver['id'] ?? null;
        $type = strtolower((string) ($approver['type'] ?? ''));
        $name = strtolower((string) ($approver['name'] ?? ''));

        if ($type === 'designation' || ($name !== '' && ! is_numeric($id))) {
            $branch = BranchUser::select('id', 'Branchhead', decrypt_data('departments', 'branch'))
                ->where('id', $locationId)
                ->first();

            if ($name === 'branch_head') {
                return isset($branch->Branchhead) && is_numeric($branch->Branchhead) ? (int) $branch->Branchhead : null;
            }

            if ($name === 'branch_dep_head' && isset($branch->departments)) {
                $departmentConfig = @unserialize($branch->departments);
                $depHead = data_get($departmentConfig, 'departmentheadid.' . $departmentId);
                return is_numeric($depHead) ? (int) $depHead : null;
            }

            if ($name === 'overall_dept_head') {
                $dept = EntityBusiness::select('id', 'overall_dept_head')->where('id', $departmentId)->first();
                return isset($dept->overall_dept_head) && is_numeric($dept->overall_dept_head) ? (int) $dept->overall_dept_head : null;
            }

            return is_numeric($id) ? (int) $id : null;
        }

        return is_numeric($id) ? (int) $id : null;
    }

    private function decodeJsonAssoc($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode(trim($value), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $value;
        }

        return $decoded;
    }

    public function insertForContracts(array $contractIds, int $actorId, bool $skipApprovalFlow = true): array
    {
        $summary = [
            'inserted' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach (array_unique(array_map('intval', $contractIds)) as $contractId) {
            if ($contractId <= 0) {
                continue;
            }

            try {
                $result = $this->insertForContract($contractId, $actorId, $skipApprovalFlow);
                $summary['inserted'] += (int) ($result['inserted'] ?? 0);
                $summary['skipped'] += (int) ($result['skipped'] ?? 0);
                $summary['failed'] += (int) ($result['failed'] ?? 0);
                if (! empty($result['errors'])) {
                    $summary['errors'] = array_merge($summary['errors'], $result['errors']);
                }
            } catch (\Throwable $e) {
                $summary['failed']++;
                $summary['errors'][] = "Contract {$contractId}: " . $e->getMessage();
            }
        }

        return $summary;
    }

    public function insertForAllMissing(int $actorId, int $limit = null): array
    {
        $query = collect($this->getMissingExecutedContractIds());

        if ($limit !== null && $limit > 0) {
            $query = $query->take($limit);
        }

        return $this->insertForContracts($query->all(), $actorId);
    }

    private function buildLocationMap(array $contractIds): array
    {
        if (empty($contractIds)) {
            return [];
        }

        $partyRows = ContractPartyData::select('custom_field_group_id', 'contract_party_location_id')
            ->whereIn('custom_field_group_id', $contractIds)
            ->where('contract_party_type', 'Internal')
            ->whereNotNull('contract_party_location_id')
            ->get();

        if ($partyRows->isEmpty()) {
            return [];
        }

        $locationIds = $partyRows->pluck('contract_party_location_id')->unique()->values();
        $branches = BranchUser::select('id', decrypt_data('BranchName', 'branch'))
            ->whereIn('id', $locationIds)
            ->get()
            ->keyBy('id');

        $mapped = [];
        foreach ($partyRows->groupBy('custom_field_group_id') as $contractId => $rows) {
            $names = [];
            foreach ($rows as $row) {
                $name = data_get($branches->get($row->contract_party_location_id), 'BranchName');
                if ($name) {
                    $names[] = $name;
                }
            }
            $names = array_values(array_unique($names));
            $mapped[$contractId] = empty($names) ? '-' : implode(', ', $names);
        }

        return $mapped;
    }

    private function safeDecrypt($value, string $key)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (! function_exists('decryptString')) {
            return $value;
        }

        try {
            return decryptString($value, $key);
        } catch (\Throwable $e) {
            return $value;
        }
    }

    private function safeEncrypt($value, string $key)
    {
        if (! function_exists('encryptString')) {
            return $value;
        }

        try {
            return encryptString($value, $key);
        } catch (\Throwable $e) {
            return $value;
        }
    }

    private function formatValue($value, $currency): string
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return '-';
        }

        $prefix = $currency ? trim((string) $currency) . ' ' : '';
        return $prefix . number_format((float) $value, 2);
    }
}
