<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Payment Type (Escrow / Regular) from the contract scope extract. Seeded as a generic
     * custom field (is_generic = 1) alongside the other V3 contract-wide fields, so it
     * renders for every contract type without touching the contracts table.
     *
     * It sits in the Contract Value category, which is where the existing payment fields
     * (schedule, terms, escrow details) live on the V3 create page.
     */
    private const FIELD_NAME = 'Payment Type';

    public function up(): void
    {
        $exists = DB::table('custom_field')
            ->where('field_name', self::FIELD_NAME)
            ->where('sub_type', 'contract')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('custom_field')->insert([
            'category'            => $this->categoryId('Contract Value', 3),
            'field_name'          => self::FIELD_NAME,
            'field_type'          => 'select',
            'field_default_value' => 'Escrow,Regular',
            'required'            => 0,
            'contract_type'       => '1',
            'is_generic'          => 1,
            'sub_type'            => 'contract',
            'status'              => 1,
            'order_id'            => 906,
        ]);
    }

    public function down(): void
    {
        DB::table('custom_field')
            ->where('is_generic', 1)
            ->where('sub_type', 'contract')
            ->where('field_name', self::FIELD_NAME)
            ->delete();
    }

    private function categoryId(string $name, int $fallback): int
    {
        $id = DB::table('custom_field_category')
            ->where('category_group', 'contract')
            ->where('category_name', $name)
            ->value('category_id');

        return $id ? (int) $id : $fallback;
    }
};
