<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('event_migrations', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 64);
            $table->string('source_table', 128);
            $table->string('target_table', 128);
            $table->unsignedInteger('batch_size')->default(1000);
            $table->unsignedBigInteger('events_migrated')->default(0);
            $table->unsignedBigInteger('events_total')->default(0);
            $table->string('status', 32)->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('verification_result')->nullable();
            $table->timestamps();

            $table->index('domain');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_migrations');
    }
};
