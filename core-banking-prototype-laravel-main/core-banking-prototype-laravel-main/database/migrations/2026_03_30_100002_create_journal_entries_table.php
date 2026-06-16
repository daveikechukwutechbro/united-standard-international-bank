<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('entry_number')->unique();
            $table->string('description');
            $table->timestamp('posted_at')->nullable();
            $table->string('status')->default('draft');
            $table->string('source_domain')->nullable();
            $table->string('source_event_id')->nullable();
            $table->uuid('reversed_by')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('posted_at');
            $table->index(['source_domain', 'source_event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
