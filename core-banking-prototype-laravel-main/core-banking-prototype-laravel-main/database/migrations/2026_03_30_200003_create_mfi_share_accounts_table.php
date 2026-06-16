<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('mfi_share_accounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->uuid('group_id')->nullable();
            $table->string('account_number')->unique();
            $table->integer('shares_purchased')->default(0);
            $table->decimal('nominal_value', 20, 2);
            $table->decimal('total_value', 20, 2)->default(0);
            $table->string('status')->default('active');
            $table->string('currency', 10)->default('USD');
            $table->decimal('dividend_balance', 20, 2)->default(0);
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('mfi_groups')->nullOnDelete();
            $table->index('user_id');
            $table->index('group_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfi_share_accounts');
    }
};
