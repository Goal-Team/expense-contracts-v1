<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('preapproval_stage', 50)->nullable()->after('contract_status');
            $table->timestamp('preapproval_completed_at')->nullable()->after('preapproval_stage');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['preapproval_stage', 'preapproval_completed_at']);
        });
    }
};
