<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('partner_integrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignUuid('partner_id')->constrained('financial_institution_partners')->cascadeOnDelete();
            $table->string('category');
            $table->string('provider');
            $table->string('status')->default('pending'); // pending, active, disabled
            $table->text('config')->nullable(); // encrypted at application level (text for encrypted:array cast)
            $table->string('webhook_url')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->unsignedInteger('error_count')->default(0);
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['partner_id', 'category', 'provider']);
            $table->index(['partner_id', 'status']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_integrations');
    }
};
