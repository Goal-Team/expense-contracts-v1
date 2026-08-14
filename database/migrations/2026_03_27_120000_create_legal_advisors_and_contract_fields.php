<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('legal_advisors')) {
            Schema::create('legal_advisors', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email_id')->unique();
                $table->string('legal_name')->nullable();
                $table->string('designation')->nullable();
                $table->string('contact', 50)->nullable();
                $table->tinyInteger('status')->default(1);
                $table->timestamps();

                $table->index('status');
            });
        }

        if (Schema::hasTable('contracts')) {
            Schema::table('contracts', function (Blueprint $table) {
                if (!Schema::hasColumn('contracts', 'legal_advisor_id')) {
                    $table->unsignedBigInteger('legal_advisor_id')->nullable()->after('owner');
                    $table->index('legal_advisor_id');
                }

                if (!Schema::hasColumn('contracts', 'legal_advisor_email')) {
                    $table->string('legal_advisor_email')->nullable()->after('legal_advisor_id');
                }

                if (!Schema::hasColumn('contracts', 'legal_finalized_notified_at')) {
                    $table->timestamp('legal_finalized_notified_at')->nullable()->after('legal_advisor_email');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contracts')) {
            Schema::table('contracts', function (Blueprint $table) {
                if (Schema::hasColumn('contracts', 'legal_finalized_notified_at')) {
                    $table->dropColumn('legal_finalized_notified_at');
                }

                if (Schema::hasColumn('contracts', 'legal_advisor_email')) {
                    $table->dropColumn('legal_advisor_email');
                }

                if (Schema::hasColumn('contracts', 'legal_advisor_id')) {
                    $table->dropIndex(['legal_advisor_id']);
                    $table->dropColumn('legal_advisor_id');
                }
            });
        }

        Schema::dropIfExists('legal_advisors');
    }
};
