<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('plugin_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('plugin_id');
            $table->unsignedBigInteger('reviewer_id')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->integer('security_score')->nullable();
            $table->text('notes')->nullable();
            $table->json('scan_results')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('plugin_id')->references('id')->on('plugins')->onDelete('cascade');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_reviews');
    }
};
