<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('contracts')) {
            return;
        }

        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'legal_contact_comment')) {
                $table->text('legal_contact_comment')->nullable()->after('legal_advisor_email');
            }

            if (!Schema::hasColumn('contracts', 'legal_requested_by_name')) {
                $table->string('legal_requested_by_name')->nullable()->after('legal_contact_comment');
            }

            if (!Schema::hasColumn('contracts', 'legal_requested_by_email')) {
                $table->string('legal_requested_by_email')->nullable()->after('legal_requested_by_name');
            }

            if (!Schema::hasColumn('contracts', 'legal_requested_at')) {
                $table->timestamp('legal_requested_at')->nullable()->after('legal_requested_by_email');
            }

            if (!Schema::hasColumn('contracts', 'legal_response_comment')) {
                $table->text('legal_response_comment')->nullable()->after('legal_requested_at');
            }

            if (!Schema::hasColumn('contracts', 'legal_responded_by_name')) {
                $table->string('legal_responded_by_name')->nullable()->after('legal_response_comment');
            }

            if (!Schema::hasColumn('contracts', 'legal_responded_by_email')) {
                $table->string('legal_responded_by_email')->nullable()->after('legal_responded_by_name');
            }

            if (!Schema::hasColumn('contracts', 'legal_responded_at')) {
                $table->timestamp('legal_responded_at')->nullable()->after('legal_responded_by_email');
            }

            if (!Schema::hasColumn('contracts', 'legal_contact_status')) {
                $table->string('legal_contact_status', 20)->default('not_contacted')->after('legal_responded_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('contracts')) {
            return;
        }

        Schema::table('contracts', function (Blueprint $table) {
            $columns = [
                'legal_contact_status',
                'legal_responded_at',
                'legal_responded_by_email',
                'legal_responded_by_name',
                'legal_response_comment',
                'legal_requested_at',
                'legal_requested_by_email',
                'legal_requested_by_name',
                'legal_contact_comment',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('contracts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
