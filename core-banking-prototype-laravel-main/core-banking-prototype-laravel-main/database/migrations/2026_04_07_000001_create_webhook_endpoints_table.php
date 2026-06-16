<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20);
            $table->string('network', 30);
            $table->unsignedInteger('shard')->default(0);
            $table->string('external_webhook_id');
            $table->text('signing_key');
            $table->string('webhook_url');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('address_count')->default(0);
            $table->timestamps();

            $table->unique(['provider', 'network', 'shard']);
            $table->index(['provider', 'network', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_endpoints');
    }
};
