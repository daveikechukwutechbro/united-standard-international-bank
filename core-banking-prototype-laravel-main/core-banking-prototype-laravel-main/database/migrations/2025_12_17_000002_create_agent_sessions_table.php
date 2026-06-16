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
        Schema::create('agent_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('session_id')->unique();
            $table->string('agent_id')->index();
            $table->string('token_hash', 64)->index();
            $table->boolean('is_revoked')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('expires_at');
            $table->dateTime('last_activity_at')->nullable();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'is_revoked']);
            $table->index(['token_hash', 'is_revoked', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_sessions');
    }
};
