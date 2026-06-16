<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('key_rotation_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('key_type')->index(); // app_key, api_token, encryption_key, jwt_secret, etc.
            $table->string('key_identifier')->unique();
            $table->integer('rotation_interval_days')->default(90);
            $table->timestamp('last_rotated_at')->nullable();
            $table->timestamp('next_rotation_at')->nullable()->index();
            $table->string('status')->default('active')->index(); // active, pending_rotation, rotated, expired
            $table->string('algorithm')->default('AES-256-GCM');
            $table->string('rotated_by')->nullable();
            $table->json('rotation_history')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('key_rotation_schedules');
    }
};
