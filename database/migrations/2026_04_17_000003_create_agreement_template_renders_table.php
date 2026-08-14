<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreement_template_renders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('agreement_template_id');
            $table->longText('merge_input_json')->nullable();
            $table->string('rendered_docx_path')->nullable();
            $table->string('rendered_pdf_path')->nullable();
            $table->string('render_status')->default('draft');
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamps();

            $table->index(['agreement_template_id'], 'agreement_template_renders_idx');
            $table->foreign('agreement_template_id')
                  ->references('id')->on('agreement_templates')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_template_renders');
    }
};
