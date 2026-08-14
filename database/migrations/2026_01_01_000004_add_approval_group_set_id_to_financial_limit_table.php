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
        if (!Schema::hasColumn('financial_limit', 'approval_group_set_id')) {
            Schema::table('financial_limit', function (Blueprint $table) {
                $table->unsignedBigInteger('approval_group_set_id')->nullable()->after('approval_required_users_terminate');

                $table->index('approval_group_set_id');

                $table->foreign('approval_group_set_id')
                    ->references('id')
                    ->on('approval_group_sets')
                    ->onDelete('set null');
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
        if (Schema::hasColumn('financial_limit', 'approval_group_set_id')) {
            Schema::table('financial_limit', function (Blueprint $table) {
                $table->dropForeign('financial_limit_approval_group_set_id_foreign');
                $table->dropIndex('financial_limit_approval_group_set_id_index');
                $table->dropColumn('approval_group_set_id');
            });
        }
    }
};
