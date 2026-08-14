<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_field', function (Blueprint $table) {
            $table->boolean('is_generic')->default(0)->after('contract_type');
        });
    }

    public function down(): void
    {
        Schema::table('custom_field', function (Blueprint $table) {
            $table->dropColumn('is_generic');
        });
    }
};
