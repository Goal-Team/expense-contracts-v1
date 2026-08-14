<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPartyFilesColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contract_parties', function (Blueprint $table) {
            if (!Schema::hasColumn('contract_parties', 'gst_file')) {
                $table->string('gst_file')->nullable()->after('gst');
            }
            if (!Schema::hasColumn('contract_parties', 'pan_file')) {
                $table->string('pan_file')->nullable()->after('pan');
            }
        });

        Schema::table('contract_parties_representative', function (Blueprint $table) {
            if (!Schema::hasColumn('contract_parties_representative', 'representative_brs')) {
                $table->string('representative_brs')->nullable()->after('representative_nationality');
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
            if (Schema::hasColumn('contract_parties', 'gst_file')) $table->dropColumn('gst_file');
            if (Schema::hasColumn('contract_parties', 'pan_file')) $table->dropColumn('pan_file');
        });

        Schema::table('contract_parties_representative', function (Blueprint $table) {
            if (Schema::hasColumn('contract_parties_representative', 'representative_brs')) $table->dropColumn('representative_brs');
        });
    }
}
