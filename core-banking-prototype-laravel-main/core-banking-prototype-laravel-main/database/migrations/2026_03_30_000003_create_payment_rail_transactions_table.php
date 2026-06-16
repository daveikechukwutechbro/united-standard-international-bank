<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('payment_rail_transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('rail');
            $table->string('external_id')->nullable();
            $table->decimal('amount', 20, 2);
            $table->string('currency', 3);
            $table->string('status')->default('initiated');
            $table->string('direction');
            $table->json('metadata')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('rail');
            $table->index('status');
            $table->index(['user_id', 'rail']);
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_rail_transactions');
    }
};
