<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreement_template_variables', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('agreement_template_id');
            $table->string('variable_key');
            $table->string('source')->nullable();
            $table->boolean('required')->default(false);
            $table->text('default_value')->nullable();
            $table->timestamps();

            $table->index(['agreement_template_id', 'variable_key'], 'agreement_template_vars_idx');
            $table->foreign('agreement_template_id')
                  ->references('id')->on('agreement_templates')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_template_variables');
    }
};
