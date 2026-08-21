<?php

namespace Modules\Contract\Services;

use App\Helpers\Helpers;
use App\Models\AddUsers;
use App\Models\BranchUser;
use App\Models\EntityBusiness;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The one place that decides which contracts the logged-in user may see.
 *
 * Deliberately NOT called a Scope. The nine classes in app/Models/Scopes are Eloquent global
 * scopes that attach themselves to a model; this is the opposite - a plain query-builder
 * fragment that a caller applies by hand. See .scratch/contracts-dashboard-perf/names.md section 2.
 *
 * The rule it writes is the SQL form of what
 * App\Http\Controllers\Controller::availableContracts() decides row by row in PHP:
 *
 *   contracts.status = 1
 *   AND contracts.department_id IN (departments this user may reach)
 *   AND EXISTS (an Internal party row for this contract in a branch this user may reach)
 *   AND whatever ContractRoledBasedScope would have added
 *
 * No decrypted value takes part in any filter, and no array of contract ids is ever built,
 * so the MariaDB 1,000-bound-parameter bug cannot happen here.
 */
class ContractVisibilityQuery
{
    /**
     * Table that holds one row per contract party. Its two join columns are TEXT today
     * (migration 2 in spec.md section 6 turns them into varchar(20)).
     */
    private const PARTY_TABLE = 'contract_party_data';

    /** @var array<int>|null branch ids, resolved once per request */
    private $branchIds = null;

    /** @var array<int>|null department ids, resolved once per request */
    private $departmentIds = null;

    /**
     * A fresh query builder over the contracts this user may see.
     */
    public function visibleContracts(): Builder
    {
        $query = DB::table('contracts');

        $this->applyTo($query, 'contracts');

        return $query;
    }

    /**
     * Add the same rule to a builder someone else owns - the approvals and tasks joins.
     * One rule, three callers, no copied SQL.
     *
     * @param  Builder  $query  builder that already has `contracts` (or $alias) in it
     * @param  string   $alias  how the contracts table is named in $query
     */
    public function applyTo(Builder $query, string $alias = 'contracts'): Builder
    {
        $query->where($alias . '.status', 1);

        $query->whereIn($alias . '.department_id', $this->reachableDepartments());

        $this->whereHasInternalPartyIn($query, $alias, $this->reachableBranches());

        $this->applyRoleRule($query, $alias);

        return $query;
    }

    /**
     * The dashboard's own location filter ($request->contractlocs). Kept separate from
     * applyTo() on purpose: this is a filter the user picked, not part of who may see what,
     * and the two must not be confused. Applied as a second EXISTS, which is what the old
     * PHP loop did - visible AND has an Internal party in one of the chosen locations.
     *
     * @param  array<int|string>  $locationIds
     */
    public function applyPartyLocationFilter(Builder $query, string $alias, array $locationIds): Builder
    {
        if (!empty($locationIds)) {
            $this->whereHasInternalPartyIn($query, $alias, $locationIds);
        }

        return $query;
    }

    /**
     * Branch ids this user may reach.
     *
     * Read through the BranchUser model on purpose, so BranchScope resolves the Super Admin
     * case for us: getEntityBranches() returns an empty array meaning "see all", the scope
     * then adds no whereIn, and the pluck comes back holding every branch of the entity. So
     * this method always returns a concrete list and the caller never has to know that an
     * empty reachable-branch list and "no branch condition" mean opposite things.
     *
     * An empty result here means the user really can reach no branch, which is what
     * availableContracts() treats as "nothing is visible" today.
     *
     * @return array<int>
     */
    public function reachableBranches(): array
    {
        if ($this->branchIds === null) {
            $this->branchIds = BranchUser::pluck('id')->all();
        }

        return $this->branchIds;
    }

    /**
     * Department ids this user may reach - same story, through EntityBusiness so
     * DepartmentScope applies.
     *
     * @return array<int>
     */
    public function reachableDepartments(): array
    {
        if ($this->departmentIds === null) {
            $this->departmentIds = EntityBusiness::pluck('id')->all();
        }

        return $this->departmentIds;
    }

    /**
     * EXISTS an Internal party row for this contract, sitting in one of $locationIds.
     *
     * @param  array<int|string>  $locationIds
     */
    private function whereHasInternalPartyIn(Builder $query, string $alias, array $locationIds): void
    {
        if (empty($locationIds)) {
            // No reachable location means no contract qualifies. Written as a false condition
            // rather than `IN ()`, which is a syntax error, and never as "no condition at all",
            // which would mean the opposite.
            $query->whereRaw('1 = 0');

            return;
        }

        // The party columns are TEXT, so the ids go over as strings and MariaDB compares
        // like for like instead of coercing every row.
        $locationIds = array_map('strval', $locationIds);

        $query->whereExists(function ($sub) use ($alias, $locationIds) {
            $sub->select(DB::raw(1))
                ->from(self::PARTY_TABLE)
                ->whereColumn(self::PARTY_TABLE . '.custom_field_group_id', $alias . '.id')
                ->where(self::PARTY_TABLE . '.contract_party_type', 'Internal')
                ->whereIn(self::PARTY_TABLE . '.contract_party_location_id', $locationIds);
        });
    }

    /**
     * The same conditions App\Models\Scopes\ContractRoledBasedScope adds through Eloquent.
     * Repeated here because the query builder does not carry a model's global scopes.
     */
    private function applyRoleRule(Builder $query, string $alias): void
    {
        if (! (bool) admin_setting('enable_role_based_data', false)) {
            return;
        }

        $role = session()->get('contractSessionUserRole');

        if ($role == 'User') {
            $query->where($alias . '.created_by', session()->get('contractUserId'));
        }

        if ($role == 'Legal') {
            $query->where($alias . '.legal_advisor_email', session()->get('contractSessionUser'));
        }

        if ($role == 'Marketing Manager') {
            $users = AddUsers::select('id')
                ->where(decrypt_datas('Manager', 'AddUsers'), session()->get('contractSessionUser'))
                ->pluck('id');

            $users[] = session()->get('contractUserId');

            $query->whereIn($alias . '.created_by', $users);
        }
    }
}
