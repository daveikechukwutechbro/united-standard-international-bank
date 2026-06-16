<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('tpp_registrations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tpp_id')->unique();
            $table->string('name');
            $table->string('client_id')->unique();
            $table->string('client_secret_hash');
            $table->text('eidas_certificate')->nullable();
            $table->json('redirect_uris');
            $table->json('roles');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpp_registrations');
    }
};
