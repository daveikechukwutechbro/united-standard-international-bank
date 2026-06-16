<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('x402_monetized_endpoints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('method', 10)->comment('HTTP method: GET, POST, PUT, DELETE, etc.');
            $table->string('path')->comment('API route path (e.g., /api/v1/premium/weather)');
            $table->string('price')->comment('USD price string (e.g., "0.001")');
            $table->string('network')->comment('CAIP-2 network identifier');
            $table->string('asset')->default('USDC')->comment('Asset symbol or contract address');
            $table->string('scheme')->default('exact')->comment('Payment scheme: exact or upto');
            $table->string('description')->nullable()->comment('Human-readable endpoint description');
            $table->string('mime_type')->default('application/json')->comment('Response MIME type');
            $table->boolean('is_active')->default(true)->comment('Whether this endpoint is actively monetized');
            $table->json('extra')->nullable()->comment('EIP-712 domain overrides and protocol extensions');
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['method', 'path', 'team_id']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('x402_monetized_endpoints');
    }
};
