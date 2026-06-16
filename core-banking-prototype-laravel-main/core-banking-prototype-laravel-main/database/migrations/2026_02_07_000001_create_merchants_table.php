<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the merchants and merchant_wallet_addresses tables.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('public_id', 64)->unique()->comment('Public-facing ID (merchant_...)');
            $table->string('display_name');
            $table->string('icon_url')->nullable();
            $table->json('accepted_assets')->comment('Array of accepted asset codes');
            $table->json('accepted_networks')->comment('Array of accepted network codes');
            $table->string('status', 20)->default('pending');
            $table->string('terminal_id', 64)->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('merchant_wallet_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->string('network', 20);
            $table->string('wallet_address', 64);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['merchant_id', 'network']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_wallet_addresses');
        Schema::dropIfExists('merchants');
    }
};
