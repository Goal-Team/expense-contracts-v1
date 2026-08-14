<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('approval_contracts')) {
            return;
        }

        Schema::table('approval_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('approval_contracts', 'file_permission')) {
                $table->string('file_permission', 20)->nullable()->after('superseded');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('approval_contracts')) {
            return;
        }

        Schema::table('approval_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('approval_contracts', 'file_permission')) {
                $table->dropColumn('file_permission');
            }
        });
    }
};
