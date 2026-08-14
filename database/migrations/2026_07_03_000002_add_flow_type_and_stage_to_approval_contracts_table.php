<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_contracts', function (Blueprint $table) {
            $table->string('flow_type', 50)->default('approval')->after('approval_type_main');
            $table->string('stage_name', 50)->nullable()->after('flow_type');
        });
    }

    public function down(): void
    {
        Schema::table('approval_contracts', function (Blueprint $table) {
            $table->dropColumn(['flow_type', 'stage_name']);
        });
    }
};
