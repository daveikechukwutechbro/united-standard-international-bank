<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('reconciliation_reports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('domain');
            $table->decimal('gl_balance', 20, 4);
            $table->decimal('domain_balance', 20, 4);
            $table->decimal('variance', 20, 4);
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('domain');
            $table->index('status');
            $table->index(['domain', 'period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_reports');
    }
};
