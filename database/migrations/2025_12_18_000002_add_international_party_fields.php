<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInternationalPartyFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contract_parties', function (Blueprint $table) {
            if (!Schema::hasColumn('contract_parties', 'corporate_registration_number')) {
                $table->string('corporate_registration_number')->nullable()->after('pan_file');
            }
            if (!Schema::hasColumn('contract_parties', 'tax_residency_certificate')) {
                $table->string('tax_residency_certificate')->nullable()->after('corporate_registration_number');
            }
            if (!Schema::hasColumn('contract_parties', 'no_permanent_establishment')) {
                $table->string('no_permanent_establishment')->nullable()->after('tax_residency_certificate');
            }
        });

        Schema::table('contract_parties_representative', function (Blueprint $table) {
            if (!Schema::hasColumn('contract_parties_representative', 'passport_number')) {
                $table->string('passport_number')->nullable()->after('representative_brs');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contract_parties', function (Blueprint $table) {
            if (Schema::hasColumn('contract_parties', 'corporate_registration_number')) $table->dropColumn('corporate_registration_number');
            if (Schema::hasColumn('contract_parties', 'tax_residency_certificate')) $table->dropColumn('tax_residency_certificate');
            if (Schema::hasColumn('contract_parties', 'no_permanent_establishment')) $table->dropColumn('no_permanent_establishment');
        });

        Schema::table('contract_parties_representative', function (Blueprint $table) {
            if (Schema::hasColumn('contract_parties_representative', 'passport_number')) $table->dropColumn('passport_number');
        });
    }
}
