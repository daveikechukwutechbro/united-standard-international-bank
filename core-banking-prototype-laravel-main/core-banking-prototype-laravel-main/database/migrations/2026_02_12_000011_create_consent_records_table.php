<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('consent_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('user_uuid')->index();
            $table->string('purpose')->index();
            $table->string('version')->default('1.0');
            $table->boolean('granted');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_uuid', 'purpose']);
            $table->index(['tenant_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_records');
    }
};
