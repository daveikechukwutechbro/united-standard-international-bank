<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('sepa_mandates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('mandate_id')->unique();
            $table->string('creditor_id');
            $table->string('creditor_name');
            $table->string('creditor_iban');
            $table->string('debtor_name');
            $table->string('debtor_iban');
            $table->string('scheme'); // CORE or B2B
            $table->string('status')->default('active'); // active, suspended, cancelled, expired
            $table->timestamp('signed_at');
            $table->timestamp('last_collection_at')->nullable();
            $table->decimal('max_amount', 20, 2)->nullable();
            $table->string('frequency')->nullable(); // daily, weekly, monthly, quarterly, annual
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('mandate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sepa_mandates');
    }
};
