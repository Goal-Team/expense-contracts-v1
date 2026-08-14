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
        if (!Schema::hasTable('approval_group_sets')) {
            Schema::create('approval_group_sets', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('financial_limit_id');
                $table->string('approval_type', 20)->default('');
                $table->timestamps();

                $table->index('financial_limit_id');
                $table->index('approval_type');
                $table->unique(['financial_limit_id', 'approval_type']);

                $table->foreign('financial_limit_id')
                    ->references('id')
                    ->on('financial_limit')
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
        if (Schema::hasTable('approval_group_sets')) {
            Schema::dropIfExists('approval_group_sets');
        }
    }
};
