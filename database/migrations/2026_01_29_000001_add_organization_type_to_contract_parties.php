<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds organization_type column and backfills values from legal_entity where applicable.
     */
    public function up()
    {
        Schema::table('contract_parties', function (Blueprint $table) {
            $table->string('organization_type')->nullable()->after('legal_entity');
        });

        // Backfill organization_type for existing rows where legal_entity is one of firm/society/trust
        DB::statement("UPDATE contract_parties SET organization_type = legal_entity WHERE LOWER(legal_entity) IN ('firm','society','trust')");

        // Optionally null out legal_entity for those rows to avoid duplication
        DB::statement("UPDATE contract_parties SET legal_entity = NULL WHERE LOWER(legal_entity) IN ('firm','society','trust')");
    }

    /**
     * Reverse the migrations.
     *
     * Drops the organization_type column.
     */
    public function down()
    {
        Schema::table('contract_parties', function (Blueprint $table) {
            $table->dropColumn('organization_type');
        });
    }
};
