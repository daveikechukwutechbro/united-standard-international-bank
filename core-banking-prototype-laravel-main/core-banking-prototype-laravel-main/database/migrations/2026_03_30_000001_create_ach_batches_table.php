<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ach_batches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('batch_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sec_code', 3);
            $table->string('status')->default('initiated');
            $table->integer('entry_count')->default(0);
            $table->decimal('total_debit', 20, 2)->default(0);
            $table->decimal('total_credit', 20, 2)->default(0);
            $table->date('settlement_date')->nullable();
            $table->boolean('same_day')->default(false);
            $table->text('file_content')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('settlement_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ach_batches');
    }
};
