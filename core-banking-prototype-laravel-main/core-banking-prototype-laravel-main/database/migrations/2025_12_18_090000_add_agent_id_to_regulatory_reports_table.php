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
        Schema::table('regulatory_reports', function (Blueprint $table) {
            // Add agent_id column for Agent Protocol reports
            // This links regulatory reports to specific agents
            $table->string('agent_id')->nullable()->after('report_type');
            $table->string('regulatory_authority')->nullable()->after('submission_reference');

            $table->index('agent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('regulatory_reports', function (Blueprint $table) {
            $table->dropIndex(['agent_id']);
            $table->dropColumn('agent_id');
            $table->dropColumn('regulatory_authority');
        });
    }
};
