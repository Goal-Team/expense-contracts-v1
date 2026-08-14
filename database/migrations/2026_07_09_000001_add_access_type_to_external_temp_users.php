<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccessTypeToExternalTempUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('external_temp_user', function (Blueprint $table) {
            if (!Schema::hasColumn('external_temp_user', 'access_type')) {
                $table->string('access_type')->default('approval')->nullable()->after('is_active');
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
        Schema::table('external_temp_user', function (Blueprint $table) {
            if (Schema::hasColumn('external_temp_user', 'access_type')) {
                $table->dropColumn('access_type');
            }
        });
    }
}
