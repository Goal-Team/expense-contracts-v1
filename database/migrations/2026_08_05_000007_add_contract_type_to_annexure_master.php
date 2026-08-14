<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annexure_master', function (Blueprint $table) {
            $table->unsignedBigInteger('contract_type')->nullable()->after('title');
            $table->index('contract_type');
        });
    }

    public function down(): void
    {
        Schema::table('annexure_master', function (Blueprint $table) {
            $table->dropIndex(['contract_type']);
            $table->dropColumn('contract_type');
        });
    }
};
