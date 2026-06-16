<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('vendor');
            $table->string('name');
            $table->string('version');
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->string('author')->nullable();
            $table->string('license')->nullable();
            $table->string('homepage')->nullable();
            $table->string('status')->default('inactive'); // inactive, active, failed, updating
            $table->json('permissions')->nullable();
            $table->json('dependencies')->nullable();
            $table->json('metadata')->nullable();
            $table->string('path');
            $table->string('entry_point')->nullable(); // ServiceProvider class
            $table->boolean('is_system')->default(false);
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['vendor', 'name']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
