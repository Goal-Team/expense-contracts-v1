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
        if (!Schema::hasTable('approval_groups')) {
            Schema::create('approval_groups', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('approval_group_set_id');
                $table->string('parent_type', 20);
                $table->string('role', 50)->default('Approver');
                $table->string('approval_type', 20)->default('sequential');
                $table->tinyInteger('auto_next_enabled')->default(0);
                $table->tinyInteger('dynamic_approver_enabled')->default(0);
                $table->integer('order_index')->default(0);
                $table->timestamps();

                $table->index('approval_group_set_id');
                $table->index('parent_type');
                $table->index('order_index');

                $table->foreign('approval_group_set_id')
                    ->references('id')
                    ->on('approval_group_sets')
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
        if (Schema::hasTable('approval_groups')) {
            Schema::dropIfExists('approval_groups');
        }
    }
};
