<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('behavioral_profiles', function (Blueprint $table) {
            // Adaptive thresholds per user
            $table->json('adaptive_thresholds')->nullable()->after('ml_features_updated_at');
            $table->json('segment_tags')->nullable()->after('adaptive_thresholds');
            $table->json('drift_metrics')->nullable()->after('segment_tags');
            $table->json('seasonal_patterns')->nullable()->after('drift_metrics');
            $table->json('sliding_window_stats')->nullable()->after('seasonal_patterns');

            // Segmentation
            $table->string('user_segment')->nullable()->after('sliding_window_stats');

            // Drift tracking
            $table->decimal('drift_score', 5, 2)->nullable()->after('user_segment');
            $table->dateTime('last_drift_check_at')->nullable()->after('drift_score');

            $table->index('user_segment');
        });
    }

    public function down(): void
    {
        Schema::table('behavioral_profiles', function (Blueprint $table) {
            $table->dropIndex(['user_segment']);
            $table->dropColumn([
                'adaptive_thresholds',
                'segment_tags',
                'drift_metrics',
                'seasonal_patterns',
                'sliding_window_stats',
                'user_segment',
                'drift_score',
                'last_drift_check_at',
            ]);
        });
    }
};
