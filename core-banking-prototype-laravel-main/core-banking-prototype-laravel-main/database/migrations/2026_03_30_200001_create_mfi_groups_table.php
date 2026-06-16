<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('mfi_groups', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('center_name')->nullable();
            $table->string('office_name')->nullable();
            $table->string('status')->default('pending');
            $table->string('meeting_frequency')->default('weekly');
            $table->string('meeting_day')->nullable();
            $table->date('activation_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('meeting_frequency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfi_groups');
    }
};
