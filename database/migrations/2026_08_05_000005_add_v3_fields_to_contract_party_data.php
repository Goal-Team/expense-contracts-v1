<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-contract snapshot of the external party's vendor code / address / contact.
     * These are pre-filled from the contract_parties master on the V3 create page but
     * remain editable, so the contract keeps what was agreed even if the master changes.
     */
    public function up(): void
    {
        Schema::table('contract_party_data', function (Blueprint $table) {
            $table->string('vendor_code', 100)->nullable()->after('contract_party_location_id');
            $table->text('party_address')->nullable()->after('vendor_code');
            $table->string('contact_details', 255)->nullable()->after('party_address');
        });

        Schema::table('contract_party_data_history', function (Blueprint $table) {
            $table->string('vendor_code', 100)->nullable()->after('contract_party_location_id');
            $table->text('party_address')->nullable()->after('vendor_code');
            $table->string('contact_details', 255)->nullable()->after('party_address');
        });
    }

    public function down(): void
    {
        Schema::table('contract_party_data', function (Blueprint $table) {
            $table->dropColumn(['vendor_code', 'party_address', 'contact_details']);
        });

        Schema::table('contract_party_data_history', function (Blueprint $table) {
            $table->dropColumn(['vendor_code', 'party_address', 'contact_details']);
        });
    }
};
