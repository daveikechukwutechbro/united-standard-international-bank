<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('x402_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('payer_address', 42)->comment('0x-prefixed Ethereum address of the payer');
            $table->string('pay_to_address', 42)->comment('0x-prefixed Ethereum address of the recipient');
            $table->string('amount')->comment('Payment amount in atomic USDC units');
            $table->string('network')->comment('CAIP-2 network identifier (e.g., eip155:8453)');
            $table->string('asset', 42)->comment('Token contract address');
            $table->string('scheme')->default('exact')->comment('Payment scheme: exact or upto');
            $table->string('status')->default('pending')->comment('SettlementStatus enum: pending, verified, settled, failed, expired');
            $table->string('transaction_hash')->nullable()->comment('On-chain transaction hash once settled');
            $table->string('endpoint_method', 10)->comment('HTTP method of the monetized endpoint');
            $table->string('endpoint_path')->comment('Path of the monetized endpoint');
            $table->string('error_reason')->nullable()->comment('Machine-readable error reason on failure');
            $table->string('error_message')->nullable()->comment('Human-readable error message on failure');
            $table->json('payment_payload')->nullable()->comment('Full x402 payment payload for audit trail');
            $table->json('extensions')->nullable()->comment('Protocol extension data');
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->comment('When payment was cryptographically verified');
            $table->timestamp('settled_at')->nullable()->comment('When on-chain settlement was confirmed');
            $table->timestamps();

            $table->index(['payer_address', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['endpoint_path', 'created_at']);
            $table->index('transaction_hash');
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('x402_payments');
    }
};
