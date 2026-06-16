<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('mfi_loan_provisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('loan_id');
            $table->string('category');
            $table->decimal('provision_amount', 20, 2);
            $table->integer('days_overdue')->default(0);
            $table->date('review_date');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('loan_id');
            $table->index('category');
            $table->index('review_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfi_loan_provisions');
    }
};
