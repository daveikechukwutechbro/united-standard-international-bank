<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * Multi-signature wallet support for M-of-N signature schemes.
     * Supports 2-of-3, 3-of-5, and other configurations with
     * hardware wallet and internal signer integration.
     */
    public function up(): void
    {
        // Multi-sig wallet configurations
        Schema::create('multi_sig_wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('address')->nullable()->comment('Wallet address once initialized');
            $table->string('chain', 50);
            $table->unsignedTinyInteger('required_signatures')->comment('M in M-of-N');
            $table->unsignedTinyInteger('total_signers')->comment('N in M-of-N');
            $table->string('status', 30)->default('pending_setup');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['chain', 'status']);
        });

        // Signers for multi-sig wallets
        Schema::create('multi_sig_wallet_signers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('multi_sig_wallet_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('hardware_wallet_association_id')->nullable();
            $table->string('signer_type', 50)->comment('hardware_ledger, hardware_trezor, internal, external');
            $table->string('public_key');
            $table->string('address')->nullable();
            $table->string('label', 100)->nullable();
            $table->unsignedTinyInteger('signer_order');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('multi_sig_wallet_id')
                ->references('id')
                ->on('multi_sig_wallets')
                ->cascadeOnDelete();

            $table->foreign('hardware_wallet_association_id')
                ->references('id')
                ->on('hardware_wallet_associations')
                ->nullOnDelete();

            $table->unique(['multi_sig_wallet_id', 'signer_order']);
            $table->index(['multi_sig_wallet_id', 'is_active']);
        });

        // Approval requests for multi-sig transactions
        Schema::create('multi_sig_approval_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('multi_sig_wallet_id');
            $table->foreignId('initiator_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('pending');
            $table->string('request_type', 50)->default('transaction')->comment('transaction, config_change, add_signer, remove_signer');
            $table->json('transaction_data');
            $table->text('raw_data_to_sign');
            $table->unsignedTinyInteger('required_signatures');
            $table->unsignedTinyInteger('current_signatures')->default(0);
            $table->string('transaction_hash')->nullable()->comment('Hash after broadcast');
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('expires_at');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('multi_sig_wallet_id')
                ->references('id')
                ->on('multi_sig_wallets')
                ->cascadeOnDelete();

            $table->index(['multi_sig_wallet_id', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index('initiator_user_id');
        });

        // Individual signer approvals
        Schema::create('multi_sig_signer_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('approval_request_id');
            $table->uuid('signer_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('decision', 20)->default('pending')->comment('pending, approved, rejected');
            $table->text('signature')->nullable();
            $table->string('public_key')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->timestamps();

            $table->foreign('approval_request_id')
                ->references('id')
                ->on('multi_sig_approval_requests')
                ->cascadeOnDelete();

            $table->foreign('signer_id')
                ->references('id')
                ->on('multi_sig_wallet_signers')
                ->cascadeOnDelete();

            $table->unique(['approval_request_id', 'signer_id']);
            $table->index(['approval_request_id', 'decision']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('multi_sig_signer_approvals');
        Schema::dropIfExists('multi_sig_approval_requests');
        Schema::dropIfExists('multi_sig_wallet_signers');
        Schema::dropIfExists('multi_sig_wallets');
    }
};
