<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the master lists the contract create page prints into its dropdowns, so
 * contracts/create and contracts/create-v3 can be measured at production-like N.
 * See .scratch/contract-create-page-perf/issues/01-seed-master-data.md
 *
 * Three lists were effectively empty and made the page impossible to measure:
 * contract_parties (1 row), country (1 row), legal_advisors (0 rows). contract_parties is the
 * one that grows organically - every counterparty a customer signs with lands there - and the
 * create page renders it three times per party block plus one address list entry per row.
 *
 * The lists that already hold production-like counts are left alone: branch 99,
 * ContractUsers 1,605, entitybusiness 214, contract_type 73, category 31.
 *
 * IMPORTANT - run with HTTP_HOST set, e.g.
 *
 *   HTTP_HOST=apollo.contracts.legality:8888 php artisan db:seed --class=CreatePageMasterDataSeeder
 *
 * config/app.php builds APP_ENCRYPTION_KEY from $_SERVER['HTTP_HOST']. Without HTTP_HOST the CLI
 * falls back to "localhost" and writes company_name values the web app can never decrypt. The
 * seeder asserts the key length before writing anything. Same rule as PerfDatasetSeeder.
 *
 * Seeded rows are identifiable without a schema change:
 *   - every id is >= 100001 (pre-existing ids are all < 300)
 *   - contract_parties.vendor_code LIKE 'SEEDCREATE-%'
 *   - legal_advisors.email_id LIKE '%@seedcreate.test'
 *   - country.UniqueCode LIKE 'SC%'
 *
 * Re-runnable: every insert is preceded by a delete of its own marked rows, so running twice
 * leaves the same counts. Nothing pre-existing is read for mutation. Only INSERTs and deletes
 * of previously seeded rows.
 *
 * Reverse with: php artisan db:seed --class=CreatePageMasterDataRollbackSeeder
 */
class CreatePageMasterDataSeeder extends Seeder
{
    /** First id used for every table this seeder writes. Pre-existing ids are all < 300. */
    public const ID_BASE = 100001;

    public const MARKER = 'SEEDCREATE';

    /**
     * Contract parties to seed.
     *
     * 5,000 is a mid-size customer's counterparty book. It is also well past the 1,000 bound
     * parameter line where this stack's whereIn silently returns zero rows, so any later change
     * that reaches for a plucked id list fails loudly here instead of in production.
     */
    public const PARTY_COUNT = 5000;

    public const ADVISOR_COUNT = 50;

    private const PARTY_TYPES = ['customer', 'vendor', 'supplier', 'partner'];

    private const PARTY_SUB_TYPES = ['organization', 'individual'];

    private const LEGAL_ENTITIES = ['corporation', 'partnership', 'individual', 'llp', 'aop', 'trust'];

    private const ROLES = ['buyer', 'seller', 'licensor', 'licensee', 'service_provider'];

    private const CITIES = [
        'Chennai', 'Bengaluru', 'Mumbai', 'Pune', 'Hyderabad', 'Kolkata', 'Delhi', 'Ahmedabad',
        'Jaipur', 'Kochi', 'Coimbatore', 'Indore', 'Lucknow', 'Nagpur', 'Surat',
    ];

    /**
     * Countries beyond the single pre-existing row (India, id 1). ISO 3166 numeric codes are
     * kept in UniqueCode with an 'SC' prefix so the rollback can find them without an id range.
     */
    private const COUNTRIES = [
        'United States', 'United Kingdom', 'Singapore', 'United Arab Emirates', 'Australia',
        'Canada', 'Germany', 'France', 'Netherlands', 'Japan', 'China', 'Hong Kong',
        'Switzerland', 'Ireland', 'Sweden', 'Norway', 'Denmark', 'Finland', 'Spain', 'Italy',
        'Belgium', 'Austria', 'Poland', 'Portugal', 'Greece', 'Czech Republic', 'Hungary',
        'Romania', 'Turkey', 'Israel', 'Saudi Arabia', 'Qatar', 'Kuwait', 'Bahrain', 'Oman',
        'Malaysia', 'Indonesia', 'Thailand', 'Vietnam', 'Philippines', 'South Korea', 'Taiwan',
        'New Zealand', 'South Africa', 'Kenya', 'Nigeria', 'Egypt', 'Brazil', 'Mexico',
        'Argentina', 'Chile', 'Colombia', 'Peru', 'Bangladesh', 'Sri Lanka', 'Nepal',
        'Pakistan', 'Mauritius', 'Luxembourg', 'Cyprus', 'Malta', 'Estonia', 'Latvia',
        'Lithuania', 'Slovakia', 'Slovenia', 'Croatia', 'Bulgaria', 'Serbia', 'Ukraine',
    ];

    public function run(): void
    {
        $this->assertEncryptionKey();

        $stateIds = DB::table('state')->pluck('id')->all();
        if (empty($stateIds)) {
            $this->command->error('The state table is empty. Seed it before running this.');

            return;
        }

        $countries = $this->seedCountries();
        $advisors  = $this->seedLegalAdvisors();
        $parties   = $this->seedContractParties($stateIds);

        $this->command->info("Seeded {$parties} contract_parties, {$countries} country, {$advisors} legal_advisors.");
        $this->command->info('Reverse with: php artisan db:seed --class=CreatePageMasterDataRollbackSeeder');
    }

    /**
     * config/app.php derives APP_ENCRYPTION_KEY from the serving hostname. Under the web server
     * it is exactly 16 bytes, which is what AES-128-CBC needs. On the CLI without HTTP_HOST the
     * fallback produces a longer key, and every company_name written with it would decrypt to
     * nothing in the browser. Stop before writing a single row.
     */
    private function assertEncryptionKey(): void
    {
        $key = (string) Config::get('app.APP_ENCRYPTION_KEY');

        if (strlen($key) !== 16) {
            throw new \RuntimeException(
                'APP_ENCRYPTION_KEY is ' . strlen($key) . ' bytes, not 16. '
                . 'Run with HTTP_HOST set: HTTP_HOST=apollo.contracts.legality:8888 php artisan db:seed --class=' . static::class
            );
        }
    }

    private function seedCountries(): int
    {
        DB::table('country')->where('UniqueCode', 'like', 'SC%')->delete();

        $rows = [];
        foreach (self::COUNTRIES as $i => $name) {
            $rows[] = [
                'id'         => self::ID_BASE + $i,
                'UniqueCode' => 'SC' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'Name'       => $name,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('country')->insert($chunk);
        }

        return count($rows);
    }

    private function seedLegalAdvisors(): int
    {
        DB::table('legal_advisors')->where('email_id', 'like', '%@seedcreate.test')->delete();

        $now  = now();
        $rows = [];
        for ($i = 0; $i < self::ADVISOR_COUNT; $i++) {
            $n      = $i + 1;
            $rows[] = [
                'id'          => self::ID_BASE + $i,
                'name'        => 'Advisor ' . str_pad((string) $n, 3, '0', STR_PAD_LEFT),
                'email_id'    => 'advisor' . $n . '@seedcreate.test',
                'legal_name'  => 'Advisory Partners ' . $n . ' LLP',
                'designation' => $n % 3 === 0 ? 'Senior Counsel' : 'Counsel',
                'contact'     => '90000' . str_pad((string) $n, 5, '0', STR_PAD_LEFT),
                'status'      => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        DB::table('legal_advisors')->insert($rows);

        return count($rows);
    }

    /**
     * @param  array<int, int>  $stateIds
     */
    private function seedContractParties(array $stateIds): int
    {
        DB::table('contract_parties')
            ->where('vendor_code', 'like', self::MARKER . '-%')
            ->delete();

        $now        = now();
        $stateCount = count($stateIds);
        $cityCount  = count(self::CITIES);
        $written    = 0;
        $rows       = [];

        for ($i = 0; $i < self::PARTY_COUNT; $i++) {
            $n       = $i + 1;
            $subType = self::PARTY_SUB_TYPES[$i % 2];
            $name    = $subType === 'individual'
                ? 'Seed Individual ' . str_pad((string) $n, 5, '0', STR_PAD_LEFT)
                : 'Seed Counterparty ' . str_pad((string) $n, 5, '0', STR_PAD_LEFT) . ' Private Limited';

            $rows[] = [
                'id'                => self::ID_BASE + $i,
                // encryptString() is how the app writes this column; the blade reads it back
                // with decryptString(). A plain value would render as the ciphertext.
                'company_name'      => encryptString($name, 'company_name'),
                'party_type'        => self::PARTY_TYPES[$i % 4],
                'party_sub_type'    => $subType,
                'entity_scope'      => $i % 7 === 0 ? 'international' : 'domestic',
                'organization_type' => null,
                'payment_type'      => $i % 3 === 0 ? 'credit' : 'cash',
                'building_no'       => (string) (($i % 200) + 1),
                'area_name'         => 'Sector ' . (($i % 40) + 1),
                'landmark'          => 'Near Landmark ' . (($i % 25) + 1),
                'city'              => self::CITIES[$i % $cityCount],
                'state'             => (string) $stateIds[$i % $stateCount],
                'pincode'           => (string) (600001 + ($i % 90000)),
                'country'           => 1,
                'company_contact'   => '98' . str_pad((string) $n, 8, '0', STR_PAD_LEFT),
                'company_email'     => 'party' . $n . '@seedcreate.test',
                'website'           => 'https://seedcreate.test/' . $n,
                'legal_entity'      => self::LEGAL_ENTITIES[$i % 6],
                'role_in_contract'  => self::ROLES[$i % 5],
                'pan'               => encryptString('AAAAA' . str_pad((string) $n, 4, '0', STR_PAD_LEFT) . 'A', 'pan'),
                'gst'               => encryptString('33AAAAA' . str_pad((string) $n, 4, '0', STR_PAD_LEFT) . 'A1Z5', 'gst'),
                'vendor_code'       => self::MARKER . '-' . str_pad((string) $n, 6, '0', STR_PAD_LEFT),
                'valid'             => 1,
                'is_related_party'  => 0,
                'approval_status'   => '0',
                'approvers'         => '[]',
                'status'            => 1,
                'created_by'        => 1,
                'updated_by'        => 1,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];

            if (count($rows) === 500) {
                DB::table('contract_parties')->insert($rows);
                $written += count($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            DB::table('contract_parties')->insert($rows);
            $written += count($rows);
        }

        return $written;
    }
}
