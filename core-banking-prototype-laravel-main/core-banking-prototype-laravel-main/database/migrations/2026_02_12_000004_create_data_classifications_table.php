<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('data_classifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('model_class')->index();
            $table->string('field_name');
            $table->string('classification_level')->index(); // public, internal, confidential, restricted
            $table->boolean('encryption_required')->default(false);
            $table->boolean('encryption_verified')->default(false);
            $table->boolean('access_logging_enabled')->default(false);
            $table->integer('retention_days')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['model_class', 'field_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_classifications');
    }
};
