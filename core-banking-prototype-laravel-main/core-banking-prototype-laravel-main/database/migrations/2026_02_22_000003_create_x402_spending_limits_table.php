<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('x402_spending_limits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('agent_id')->comment('Agent DID or user identifier');
            $table->string('agent_type')->default('ai')->comment('Agent type: ai, user, service');
            $table->string('daily_limit')->comment('Maximum daily spend in atomic units');
            $table->string('spent_today')->default('0')->comment('Amount spent today in atomic units');
            $table->string('per_transaction_limit')->nullable()->comment('Maximum spend per transaction in atomic units');
            $table->boolean('auto_pay_enabled')->default(false)->comment('Whether automatic payment is enabled');
            $table->timestamp('limit_resets_at')->comment('Timestamp when daily limit resets');
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['agent_id', 'team_id']);
            $table->index('agent_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('x402_spending_limits');
    }
};
