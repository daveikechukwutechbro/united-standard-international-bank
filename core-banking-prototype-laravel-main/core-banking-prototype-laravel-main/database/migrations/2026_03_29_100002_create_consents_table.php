<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tpp_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('awaiting_authorization');
            $table->json('permissions');
            $table->json('account_ids')->nullable();
            $table->timestamp('expires_at');
            $table->integer('frequency_per_day')->default(4);
            $table->boolean('recurring_indicator')->default(false);
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('tpp_id')->references('tpp_id')->on('tpp_registrations');
            $table->index(['user_id', 'status']);
            $table->index(['tpp_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
