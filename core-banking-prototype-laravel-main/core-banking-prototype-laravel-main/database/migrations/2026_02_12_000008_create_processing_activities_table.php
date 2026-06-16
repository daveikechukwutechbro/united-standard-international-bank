<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('processing_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('name');
            $table->text('purpose');
            $table->string('legal_basis')->index();
            $table->json('data_categories')->nullable();
            $table->json('data_subjects')->nullable();
            $table->json('recipients')->nullable();
            $table->string('retention_period')->nullable();
            $table->json('international_transfers')->nullable();
            $table->text('security_measures')->nullable();
            $table->string('controller_name')->nullable();
            $table->string('controller_contact')->nullable();
            $table->string('dpo_contact')->nullable();
            $table->string('status')->default('active')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processing_activities');
    }
};
