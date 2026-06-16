<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('posting_rules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('trigger_event');
            $table->string('debit_account');
            $table->string('credit_account');
            $table->string('amount_expression');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index('trigger_event');
            $table->index('is_active');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posting_rules');
    }
};
