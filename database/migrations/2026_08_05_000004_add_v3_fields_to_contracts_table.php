<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('tenure', 100)->nullable()->after('contract_name_hash');
            $table->string('price_revision_type', 20)->nullable()->after('tenure');
            $table->decimal('price_revision_value', 15, 2)->nullable()->after('price_revision_type');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['tenure', 'price_revision_type', 'price_revision_value']);
        });
    }
};
