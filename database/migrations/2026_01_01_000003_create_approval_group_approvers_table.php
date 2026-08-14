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
        if (!Schema::hasTable('approval_group_approvers')) {
            Schema::create('approval_group_approvers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('approval_group_id');
                $table->integer('approver_id')->default(0);
                $table->string('approver_type', 20)->default('name');
                $table->string('approver_name');
                $table->string('approver_email');
                $table->integer('order_index')->default(0);
                $table->timestamps();

                $table->index('approval_group_id');
                $table->index('order_index');

                $table->foreign('approval_group_id')
                    ->references('id')
                    ->on('approval_groups')
                    ->onDelete('cascade');
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
        if (Schema::hasTable('approval_group_approvers')) {
            Schema::dropIfExists('approval_group_approvers');
        }
    }
};
