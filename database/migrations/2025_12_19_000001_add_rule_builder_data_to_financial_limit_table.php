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
        if (!Schema::hasColumn('financial_limit', 'rule_builder_data')) {
            Schema::table('financial_limit', function (Blueprint $table) {
                $table->text('rule_builder_data')->nullable()->after('approval_required_users_terminate');
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
        if (Schema::hasColumn('financial_limit', 'rule_builder_data')) {
            Schema::table('financial_limit', function (Blueprint $table) {
                $table->dropColumn('rule_builder_data');
            });
        }
    }
};