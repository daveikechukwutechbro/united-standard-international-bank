<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the activity_feed_items table (CQRS read model for activity feed).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('activity_feed_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('activity_type', 30);
            $table->string('merchant_name')->nullable();
            $table->string('merchant_icon_url')->nullable();
            $table->decimal('amount', 20, 8)->comment('Signed: negative = outflow');
            $table->string('asset', 10);
            $table->string('network', 20)->nullable();
            $table->string('status', 20)->default('pending');
            $table->boolean('protected')->default(false)->comment('Shield/privacy status');
            $table->string('reference_type')->nullable()->comment('Polymorphic type');
            $table->uuid('reference_id')->nullable()->comment('Polymorphic ID');
            $table->string('from_address', 64)->nullable();
            $table->string('to_address', 64)->nullable();
            $table->dateTime('occurred_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'occurred_at', 'id'], 'afi_cursor_idx');
            $table->index(['user_id', 'activity_type', 'occurred_at'], 'afi_filtered_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_feed_items');
    }
};
