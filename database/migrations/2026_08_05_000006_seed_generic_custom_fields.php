<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The five contract-wide fields introduced by Contract Create V3. They are stored as
     * generic custom fields (is_generic = 1) so they render for every contract type
     * without touching the contracts table.
     *
     * `category` maps to custom_field_category.category_id. That table is not created by a
     * migration in this repo, so the ids are resolved by name at run time with the
     * conventional id as a fallback.
     */
    private array $fields = [
        [
            'field_name'          => 'Relationship with Apollo Group',
            'field_type'          => 'select',
            'field_default_value' => 'Related Party,Non-RP',
            'required'            => 1,
            'category_name'       => 'Basic Details',
            'category_fallback'   => 1,
            'order_id'            => 901,
        ],
        [
            'field_name'          => 'Service Commencement Date',
            'field_type'          => 'date',
            'field_default_value' => null,
            'required'            => 0,
            'category_name'       => 'Contract Duration',
            'category_fallback'   => 2,
            'order_id'            => 902,
        ],
        [
            'field_name'          => 'Termination Notice Period',
            'field_type'          => 'text',
            'field_default_value' => null,
            'required'            => 0,
            'category_name'       => 'Contract Duration',
            'category_fallback'   => 2,
            'order_id'            => 903,
        ],
        [
            'field_name'          => 'Contract Scope Extract',
            'field_type'          => 'textarea',
            'field_default_value' => null,
            'required'            => 0,
            'category_name'       => 'Miscellaneous',
            'category_fallback'   => 4,
            'order_id'            => 904,
        ],
        [
            'field_name'          => 'Special Obligations',
            'field_type'          => 'textarea',
            'field_default_value' => null,
            'required'            => 0,
            'category_name'       => 'Miscellaneous',
            'category_fallback'   => 4,
            'order_id'            => 905,
        ],
    ];

    public function up(): void
    {
        foreach ($this->fields as $field) {
            $exists = DB::table('custom_field')
                ->where('field_name', $field['field_name'])
                ->where('sub_type', 'contract')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('custom_field')->insert([
                'category'            => $this->categoryId($field['category_name'], $field['category_fallback']),
                'field_name'          => $field['field_name'],
                'field_type'          => $field['field_type'],
                'field_default_value' => $field['field_default_value'],
                'required'            => $field['required'],
                'contract_type'       => '1',
                'is_generic'          => 1,
                'sub_type'            => 'contract',
                'status'              => 1,
                'order_id'            => $field['order_id'],
            ]);
        }
    }

    public function down(): void
    {
        DB::table('custom_field')
            ->where('is_generic', 1)
            ->where('sub_type', 'contract')
            ->whereIn('field_name', array_column($this->fields, 'field_name'))
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
