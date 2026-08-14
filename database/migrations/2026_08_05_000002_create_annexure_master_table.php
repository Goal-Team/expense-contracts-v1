<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annexure_master', function (Blueprint $table) {
            $table->id();
            $table->string('annexure_name');
            $table->string('title')->nullable();
            $table->boolean('status')->default(1);
            $table->string('sample_file')->nullable();
            $table->string('sample_file_name')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annexure_master');
    }
};
