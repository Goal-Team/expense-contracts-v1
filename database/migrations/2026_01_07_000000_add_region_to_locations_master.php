<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('locations_master', 'region')) {
            Schema::table('locations_master', function (Blueprint $table) {
                $table->string('region', 100)->nullable()->after('location_code');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('locations_master', 'region')) {
            Schema::table('locations_master', function (Blueprint $table) {
                $table->dropColumn('region');
            });
        }
    }
};