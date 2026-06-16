<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('consent_access_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('consent_id');
            $table->string('tpp_id');
            $table->string('endpoint');
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->foreign('consent_id')->references('id')->on('consents')->cascadeOnDelete();
            $table->index(['consent_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_access_logs');
    }
};
