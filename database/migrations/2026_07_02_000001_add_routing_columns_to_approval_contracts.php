<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_contracts', function (Blueprint $table) {
            $table->string('next_group_on_approve', 50)->default('')->after('approval_type_main');
            $table->string('next_group_on_reject', 50)->default('')->after('next_group_on_approve');
        });
    }

    public function down(): void
    {
        Schema::table('approval_contracts', function (Blueprint $table) {
            $table->dropColumn(['next_group_on_approve', 'next_group_on_reject']);
        });
    }
};
