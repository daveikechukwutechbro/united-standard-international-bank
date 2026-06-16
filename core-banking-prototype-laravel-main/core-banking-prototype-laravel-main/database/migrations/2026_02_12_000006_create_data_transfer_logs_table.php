<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('data_transfer_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('from_region')->index();
            $table->string('to_region')->index();
            $table->string('data_type');
            $table->text('reason')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('status')->default('logged')->index(); // logged, approved, denied
            $table->string('user_uuid')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['from_region', 'to_region']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_transfer_logs');
    }
};
