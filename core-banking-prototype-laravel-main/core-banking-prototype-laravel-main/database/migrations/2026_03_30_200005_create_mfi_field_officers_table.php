<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('mfi_field_officers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->unique()->constrained('users');
            $table->string('name');
            $table->string('territory')->nullable();
            $table->integer('client_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('territory');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfi_field_officers');
    }
};
