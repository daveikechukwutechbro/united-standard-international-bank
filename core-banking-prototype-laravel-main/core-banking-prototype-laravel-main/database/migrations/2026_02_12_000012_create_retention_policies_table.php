<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('retention_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('data_type')->index();
            $table->string('model_class')->nullable()->index();
            $table->integer('retention_days');
            $table->string('action')->default('delete')->index();
            $table->boolean('enabled')->default(true);
            $table->text('description')->nullable();
            $table->timestamp('last_enforced_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'data_type', 'model_class']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retention_policies');
    }
};
