<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Removes everything PerfDatasetSeeder inserted, returning apollo_contracts_expense to its
 * pre-seed row counts (18 contracts / 40 contract_party_data / 127 approval_contracts).
 *
 *   HTTP_HOST=apollo.contracts.legality:8888 php artisan db:seed --class=PerfDatasetRollbackSeeder
 *
 * Deletions are doubly constrained - by the SEEDPERF marker in an existing text column AND by
 * the id >= 100001 range the seeder assigns - so a marker typo can never reach a real row.
 * No pre-existing row can match either condition (all pre-existing ids are < 200).
 */
class PerfDatasetRollbackSeeder extends Seeder
{
    public function run(): void
    {
        $base   = PerfDatasetSeeder::ID_BASE;
        $marker = PerfDatasetSeeder::MARKER;

        $approvals = DB::table('approval_contracts')
            ->where('id', '>=', $base)
            ->where('unique_id', 'like', 'seedperf%')
            ->delete();

        $parties = DB::table('contract_party_data')
            ->where('id', '>=', $base)
            ->where('party_address', 'like', '%[' . $marker . ']%')
            ->delete();

        $contracts = DB::table('contracts')
            ->where('id', '>=', $base)
            ->where('contract_unique_id', 'like', $marker . '-%')
            ->delete();

        $this->command->info("Deleted {$contracts} contracts, {$parties} contract_party_data, {$approvals} approval_contracts.");
        $this->command->info('Remaining: contracts=' . DB::table('contracts')->count()
            . ' contract_party_data=' . DB::table('contract_party_data')->count()
            . ' approval_contracts=' . DB::table('approval_contracts')->count());

        $leftover = DB::table('contracts')->where('contract_unique_id', 'like', $marker . '-%')->count()
            + DB::table('contract_party_data')->where('party_address', 'like', '%[' . $marker . ']%')->count()
            + DB::table('approval_contracts')->where('unique_id', 'like', 'seedperf%')->count();

        if ($leftover > 0) {
            $this->command->warn("WARNING: {$leftover} marked rows remain (id below " . $base . '?).');
        }
    }
}
