<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->index(['provider', 'status'], 'kyc_verif_provider_status_idx');
            $table->index(['provider', 'provider_reference'], 'kyc_verif_provider_ref_compound_idx');
        });
    }

    public function down(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->dropIndex('kyc_verif_provider_status_idx');
            $table->dropIndex('kyc_verif_provider_ref_compound_idx');
        });
    }
};
