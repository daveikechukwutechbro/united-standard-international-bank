<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('compliance_evidence', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('evidence_type')->index(); // access_logs, config_snapshot, change_log, deployment_record
            $table->string('period')->index(); // e.g. 'Q1-2026', '2026-01'
            $table->json('data');
            $table->string('integrity_hash', 64); // SHA-256
            $table->string('collected_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['evidence_type', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compliance_evidence');
    }
};
