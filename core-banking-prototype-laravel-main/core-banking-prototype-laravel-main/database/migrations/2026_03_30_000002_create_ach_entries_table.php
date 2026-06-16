<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ach_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('batch_id');
            $table->string('trace_number')->unique();
            $table->string('routing_number', 9);
            $table->string('account_number');
            $table->decimal('amount', 20, 2);
            $table->string('transaction_code', 2);
            $table->string('individual_name');
            $table->string('individual_id')->nullable();
            $table->text('addenda')->nullable();
            $table->string('status')->default('initiated');
            $table->string('return_code')->nullable();
            $table->timestamps();

            $table->foreign('batch_id')
                ->references('id')
                ->on('ach_batches')
                ->cascadeOnDelete();

            $table->index('batch_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ach_entries');
    }
};
