<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('mfi_group_members', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('group_id');
            $table->foreignId('user_id')->constrained('users');
            $table->string('role')->default('member');
            $table->date('joined_at');
            $table->date('left_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('mfi_groups')->cascadeOnDelete();
            $table->index('group_id');
            $table->index('user_id');
            $table->index('role');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfi_group_members');
    }
};
