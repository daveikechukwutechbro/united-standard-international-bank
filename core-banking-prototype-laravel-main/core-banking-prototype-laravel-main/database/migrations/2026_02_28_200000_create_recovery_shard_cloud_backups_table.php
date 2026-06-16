<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('recovery_shard_cloud_backups', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_id');
            $table->string('backup_provider'); // icloud, google_drive, manual
            $table->string('encrypted_shard_hash');
            $table->string('shard_version');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_id', 'backup_provider'], 'shard_cloud_user_device_provider_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recovery_shard_cloud_backups');
    }
};
