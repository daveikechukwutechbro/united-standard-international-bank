<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the payment_intents table for mobile merchant payment lifecycle.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('public_id', 64)->unique()->comment('Public-facing ID (pi_...)');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->string('asset', 10);
            $table->string('network', 20);
            $table->decimal('amount', 20, 8);
            $table->string('status', 20)->default('created');
            $table->boolean('shield_enabled')->default(false);
            $table->json('fees_estimate')->nullable();
            $table->string('tx_hash', 128)->nullable()->index();
            $table->string('tx_explorer_url')->nullable();
            $table->unsignedInteger('confirmations')->default(0);
            $table->unsignedInteger('required_confirmations')->default(1);
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->string('cancel_reason', 50)->nullable();
            $table->string('idempotency_key', 128)->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->dateTime('expires_at');
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'expires_at']);
            $table->index(['merchant_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_intents');
    }
};
