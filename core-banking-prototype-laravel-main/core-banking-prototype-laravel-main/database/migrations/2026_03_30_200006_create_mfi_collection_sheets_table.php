<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('mfi_collection_sheets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('officer_id');
            $table->uuid('group_id');
            $table->date('collection_date');
            $table->decimal('expected_amount', 20, 2);
            $table->decimal('collected_amount', 20, 2)->default(0);
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('officer_id')->references('id')->on('mfi_field_officers')->cascadeOnDelete();
            $table->foreign('group_id')->references('id')->on('mfi_groups')->cascadeOnDelete();
            $table->index('officer_id');
            $table->index('group_id');
            $table->index('collection_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfi_collection_sheets');
    }
};
