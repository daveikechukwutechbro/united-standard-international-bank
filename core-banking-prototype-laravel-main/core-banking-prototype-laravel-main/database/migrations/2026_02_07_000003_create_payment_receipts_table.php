<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the payment_receipts table for transaction receipt generation.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('public_id', 64)->unique()->comment('Public-facing ID (rcpt_...)');
            $table->foreignUuid('payment_intent_id')->nullable()->constrained('payment_intents')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('merchant_name')->comment('Snapshot at receipt creation time');
            $table->decimal('amount', 20, 8);
            $table->string('asset', 10);
            $table->string('network', 20);
            $table->string('tx_hash', 128)->nullable();
            $table->string('network_fee', 50)->comment('Display string like "0.01 USD"');
            $table->string('pdf_path')->nullable();
            $table->string('share_token', 64)->unique();
            $table->dateTime('transaction_at');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_receipts');
    }
};
