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
        if (!Schema::hasColumn('financial_limit', 'approval_groups')) {
            Schema::table('financial_limit', function (Blueprint $table) {
                $table->text('approval_groups')->nullable()->after('rule_builder_data');
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
        if (Schema::hasColumn('financial_limit', 'approval_groups')) {
            Schema::table('financial_limit', function (Blueprint $table) {
                $table->dropColumn('approval_groups');
            });
        }
    }
};