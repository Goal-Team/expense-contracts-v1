<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_parties', function (Blueprint $table) {
            $table->string('vendor_code', 100)->nullable()->after('pan');
            $table->string('active_vendor_code', 100)->nullable()->after('vendor_code');
            $table->tinyInteger('valid')->default(0)->after('active_vendor_code');
        });
    }

    public function down(): void
    {
        Schema::table('contract_parties', function (Blueprint $table) {
            $table->dropColumn(['vendor_code', 'active_vendor_code', 'valid']);
        });
    }
};
