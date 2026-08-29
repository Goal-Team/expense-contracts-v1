<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Removes everything CreatePageMasterDataSeeder inserted, returning apollo_contracts_expense to
 * its pre-seed counts (1 contract_parties / 1 country / 0 legal_advisors).
 *
 *   HTTP_HOST=apollo.contracts.legality:8888 php artisan db:seed --class=CreatePageMasterDataRollbackSeeder
 *
 * Every delete is doubly constrained - by the marker in a plain text column AND by the id range
 * the seeder assigns - so a marker typo can never reach a real row. No pre-existing row can
 * match either condition (all pre-existing ids are < 300).
 */
class CreatePageMasterDataRollbackSeeder extends Seeder
{
    public function run(): void
    {
        $base   = CreatePageMasterDataSeeder::ID_BASE;
        $marker = CreatePageMasterDataSeeder::MARKER;

        $parties = DB::table('contract_parties')
            ->where('id', '>=', $base)
            ->where('vendor_code', 'like', $marker . '-%')
            ->delete();

        $advisors = DB::table('legal_advisors')
            ->where('id', '>=', $base)
            ->where('email_id', 'like', '%@seedcreate.test')
            ->delete();

        $countries = DB::table('country')
            ->where('id', '>=', $base)
            ->where('UniqueCode', 'like', 'SC%')
            ->delete();

        $this->command->info("Deleted {$parties} contract_parties, {$countries} country, {$advisors} legal_advisors.");
    }
}
