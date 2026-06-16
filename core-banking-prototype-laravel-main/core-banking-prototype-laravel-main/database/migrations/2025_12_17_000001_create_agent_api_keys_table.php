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
        Schema::create('agent_api_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key_id')->unique();
            $table->string('agent_id')->index();
            $table->string('name');
            $table->string('key_hash', 64)->index();
            $table->string('key_prefix', 8);
            $table->json('scopes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'is_active']);
            $table->index(['key_hash', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_api_keys');
    }
};
