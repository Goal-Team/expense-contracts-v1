<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreement_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('contract_type')->nullable();
            $table->string('payment_type')->nullable();
            $table->unsignedBigInteger('entity_type_id')->nullable();
            $table->string('template_name')->nullable();
            $table->longText('template_html')->nullable();
            $table->string('source_docx_path')->nullable();
            $table->string('source_docx_filename')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('version_no')->default(1);
            $table->string('published_scope_key')->nullable()->unique();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['contract_type', 'payment_type', 'entity_type_id'], 'agreement_templates_scope_idx');
            $table->index(['status'], 'agreement_templates_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_templates');
    }
};
