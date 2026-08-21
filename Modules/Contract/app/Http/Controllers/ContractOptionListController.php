<?php

namespace Modules\Contract\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BranchUser;
use App\Models\ContractType;
use Illuminate\Support\Facades\Log;

/**
 * Shared dropdown option lists (spec.md section 8, names.md section 5).
 *
 * One combined endpoint, not one per list: the dashboard needs two lists and the
 * contract list will need five, and several small requests on a page that already
 * queues connections is the main way this change could make the page slower.
 *
 * The lists are read through the Eloquent models on purpose. BranchUser carries
 * BranchScope and ContractType carries ContractTypeScope as global scopes, so a
 * location-scoped user gets the same rows here as the page gives them. Nothing in
 * this class removes a global scope.
 */
class ContractOptionListController extends Controller
{
    /**
     * The lists this endpoint knows how to build, by the key the caller asks for.
     */
    public const KNOWN_LISTS = ['contractTypes', 'branches'];

    /**
     * Ten minutes, the same window as the cached party list at
     * ContractController::contractCreatePartyListV2().
     */
    public const CACHE_MINUTES = 10;

    public function __construct()
    {
        if (Controller::checkCurrentAuth("Contracts") != 1) {
            return abort('404');
        }
    }

    /**
     * Return the requested option lists in one JSON object.
     *
     * GET contracts/option-lists?lists=contractTypes,branches
     * The lists parameter is optional; with no parameter every known list is returned.
     *
     * {
     *   "ok": true,
     *   "lists": {
     *     "contractTypes": [{"id": 12, "text": "Service Agreement"}, ...],
     *     "branches":      [{"id": 5,  "text": "Acme Legal Pvt Ltd"}, ...]
     *   }
     * }
     *
     * On failure: {"ok": false, "lists": {}} with HTTP 500, so the browser side can
     * tell "no options" apart from "options arrived and there are none".
     */
    public function optionLists(Request $request)
    {
        $requested = $this->requestedLists($request);

        try {
            $version = $this->optionListsVersion();

            // The session identity is part of the key because BranchScope filters on
            // it. Without it one user's visible branch set could be served to another.
            $cacheKey = 'contract:optionlists:v1:' . md5(json_encode([
                $requested,
                session()->get('contractSessionUserRole'),
                session()->get('contractUserId'),
                session()->get('contractSessionUser'),
                session()->get('contractSessionEntity'),
                $version,
            ]));

            $lists = cache()->remember($cacheKey, now()->addMinutes(self::CACHE_MINUTES), function () use ($requested) {
                $built = [];

                foreach ($requested as $list) {
                    if ($list === 'contractTypes') {
                        $built['contractTypes'] = $this->contractTypeOptions();
                    }

                    if ($list === 'branches') {
                        $built['branches'] = $this->branchOptions();
                    }
                }

                return $built;
            });

            Log::debug('optionLists served', [
                'lists' => $requested,
                'counts' => array_map('count', $lists),
            ]);

            return response()->json([
                'ok' => true,
                'lists' => $lists,
            ]);
        } catch (\Throwable $e) {
            // No option text in the log - these rows carry decrypted branch names.
            Log::error('optionLists failed', [
                'lists' => $requested,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'lists' => [],
            ], 500);
        }
    }

    /**
     * The COUNT(*) / MAX() cache stamp, copying the pattern at
     * ContractController.php:6879-6896.
     *
     * Neither `branch` nor `contract_type` has an `updated_at` column (checked
     * against information_schema), so the newest primary key stands in for
     * MAX(updated_at). Rows added or removed bust the cache at once; a rename of an
     * existing row waits out the ten minutes. Both stamp queries run through the
     * models, so the same global scopes that shape the lists also shape the stamp.
     */
    private function optionListsVersion()
    {
        try {
            $typeStamp = ContractType::selectRaw(
                "COUNT(*) as row_count, COALESCE(MAX(contract_type_id), 0) as last_change"
            )->first();

            $branchStamp = BranchUser::selectRaw(
                "COUNT(*) as row_count, COALESCE(MAX(id), 0) as last_change"
            )->first();

            return 'ct' . ($typeStamp->row_count ?? 0) . '.' . ($typeStamp->last_change ?? 0)
                . '|br' . ($branchStamp->row_count ?? 0) . '.' . ($branchStamp->last_change ?? 0);
        } catch (\Throwable $e) {
            // If the stamp query fails, fall back to a short time bucket rather than
            // serving a list that never refreshes.
            Log::warning('optionLists version stamp failed', ['message' => $e->getMessage()]);

            return 't' . floor(time() / 60);
        }
    }

    /**
     * Contract types, same query shape and same option text as the blade loop at
     * viewDashboard1.blade.php:342 built from ContractDashboardController::dashDetails.
     */
    private function contractTypeOptions()
    {
        $options = [];

        foreach (ContractType::get() as $contractType) {
            $options[] = [
                'id' => $contractType->contract_type_id,
                'text' => (string) $contractType->contract_type,
            ];
        }

        return $options;
    }

    /**
     * Branches, same option text as the blade loop at viewDashboard1.blade.php:351.
     *
     * Only `id` and the decrypted `LegalName` are selected. The dashboard select only
     * ever printed LegalName; the other ten AES_DECRYPT columns in dashDetails were
     * never used by these options, and dropping them is part of the point of this
     * change.
     */
    private function branchOptions()
    {
        $branches = BranchUser::select(
            'id',
            decrypt_data('LegalName', 'branch')
        )->get();

        $options = [];

        foreach ($branches as $branch) {
            $options[] = [
                'id' => $branch->id,
                'text' => (string) $branch->LegalName,
            ];
        }

        return $options;
    }

    /**
     * Which lists the caller asked for, filtered to the ones this class knows.
     * Anything unknown is dropped rather than erroring, so a newer page asking for a
     * list an older deploy does not have still gets the lists it does have.
     */
    private function requestedLists(Request $request)
    {
        $raw = $request->input('lists');

        if (is_string($raw)) {
            $raw = explode(',', $raw);
        }

        if (!is_array($raw) || count($raw) === 0) {
            return self::KNOWN_LISTS;
        }

        $wanted = [];

        foreach ($raw as $name) {
            $name = trim((string) $name);

            if (in_array($name, self::KNOWN_LISTS, true) && !in_array($name, $wanted, true)) {
                $wanted[] = $name;
            }
        }

        if (count($wanted) === 0) {
            Log::warning('optionLists asked for no list this endpoint knows');

            return self::KNOWN_LISTS;
        }

        return $wanted;
    }
}
