<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('journal_entry_id');
            $table->string('account_code');
            $table->decimal('debit_amount', 20, 4)->default(0);
            $table->decimal('credit_amount', 20, 4)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->string('narrative')->nullable();
            $table->timestamps();

            $table->foreign('journal_entry_id')
                ->references('id')
                ->on('journal_entries')
                ->cascadeOnDelete();

            $table->index('journal_entry_id');
            $table->index('account_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};
