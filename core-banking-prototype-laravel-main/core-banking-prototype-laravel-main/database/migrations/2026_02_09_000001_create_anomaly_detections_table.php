<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('anomaly_detections', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Polymorphic entity reference
            $table->uuid('entity_id');
            $table->string('entity_type');
            $table->foreignId('user_id')->nullable()->constrained();

            // Classification
            $table->string('anomaly_type'); // statistical|behavioral|velocity|geolocation
            $table->string('detection_method'); // z_score|iqr|isolation_forest|lof|adaptive_threshold|drift_detection|sliding_window|burst|impossible_travel|ip_reputation|geo_clustering
            $table->string('status')->default('detected'); // detected|investigating|confirmed|false_positive|resolved

            // Scores
            $table->decimal('anomaly_score', 5, 2)->default(0); // 0-100
            $table->decimal('confidence', 5, 4)->default(0); // 0-1
            $table->string('severity')->default('low'); // low|medium|high|critical

            // Detail payloads
            $table->json('features')->nullable();
            $table->json('thresholds')->nullable();
            $table->json('explanation')->nullable();
            $table->json('raw_scores')->nullable();
            $table->json('context_snapshot')->nullable();
            $table->json('baseline_snapshot')->nullable();

            // ML metadata
            $table->string('model_version')->nullable();
            $table->string('pipeline_run_id')->nullable();
            $table->boolean('is_real_time')->default(true);

            // Links to existing fraud domain
            $table->uuid('fraud_score_id')->nullable();
            $table->uuid('fraud_case_id')->nullable();

            // Feedback loop
            $table->string('feedback_outcome')->nullable();
            $table->text('feedback_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['entity_id', 'entity_type']);
            $table->index(['anomaly_type', 'created_at']);
            $table->index(['user_id', 'anomaly_type', 'created_at']);
            $table->index(['severity', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anomaly_detections');
    }
};
