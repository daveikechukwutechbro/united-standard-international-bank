<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('anomaly_detections', function (Blueprint $table) {
            // Missing indexes on FK/query columns
            $table->index('fraud_score_id');
            $table->index('fraud_case_id');
            $table->index('pipeline_run_id');

            // Audit trail fields
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->string('previous_status')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('anomaly_detections', function (Blueprint $table) {
            $table->dropIndex(['fraud_score_id']);
            $table->dropIndex(['fraud_case_id']);
            $table->dropIndex(['pipeline_run_id']);

            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['reviewed_at', 'previous_status']);
        });
    }
};
