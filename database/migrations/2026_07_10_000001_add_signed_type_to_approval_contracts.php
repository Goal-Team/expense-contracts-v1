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
            // Defensive: some environments were created from an SQL dump that
            // already contains signed_png. Only add it where it is missing.
            if (!Schema::hasColumn('approval_contracts', 'signed_png')) {
                $table->longText('signed_png')->nullable();
            }

            if (!Schema::hasColumn('approval_contracts', 'signed_type')) {
                $table->string('signed_type', 50)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('approval_contracts')) {
            return;
        }

        Schema::table('approval_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('approval_contracts', 'signed_type')) {
                $table->dropColumn('signed_type');
            }
        });
    }
};
