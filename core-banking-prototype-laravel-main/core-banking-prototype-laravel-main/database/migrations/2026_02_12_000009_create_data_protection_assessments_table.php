<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('data_protection_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('processing_activity_id')->nullable()->index();
            $table->json('risks')->nullable();
            $table->json('mitigations')->nullable();
            $table->integer('risk_score')->default(0);
            $table->string('status')->default('draft')->index();
            $table->string('assessor')->nullable();
            $table->string('reviewer')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_protection_assessments');
    }
};
