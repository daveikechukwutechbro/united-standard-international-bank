<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mobile_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_id', 100)->unique()->comment('Unique device identifier from mobile app');
            $table->enum('platform', ['ios', 'android']);
            $table->string('push_token', 500)->nullable()->comment('FCM or APNS token');
            $table->string('device_name', 100)->nullable()->comment('User-friendly device name');
            $table->string('device_model', 100)->nullable()->comment('Hardware model');
            $table->string('os_version', 50)->nullable()->comment('Operating system version');
            $table->string('app_version', 20)->comment('Mobile app version');
            $table->boolean('biometric_enabled')->default(false);
            $table->text('biometric_public_key')->nullable()->comment('ECDSA P-256 public key for biometric auth');
            $table->string('biometric_key_id', 100)->nullable()->comment('Key identifier for rotation');
            $table->dateTime('last_active_at')->nullable();
            $table->dateTime('biometric_enabled_at')->nullable();
            $table->boolean('is_trusted')->default(false)->comment('Whether device has been explicitly trusted');
            $table->dateTime('trusted_at')->nullable();
            $table->string('trusted_by')->nullable()->comment('Admin who trusted the device');
            $table->boolean('is_blocked')->default(false);
            $table->dateTime('blocked_at')->nullable();
            $table->string('blocked_reason')->nullable();
            $table->json('metadata')->nullable()->comment('Additional device metadata');
            $table->timestamps();

            $table->index(['user_id', 'platform']);
            $table->index('push_token');
            $table->index(['user_id', 'is_blocked']);
            $table->index('last_active_at');
        });

        Schema::create('mobile_device_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('mobile_device_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_token', 64)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->dateTime('last_activity_at');
            $table->dateTime('expires_at');
            $table->boolean('is_biometric_session')->default(false);
            $table->timestamps();

            $table->foreign('mobile_device_id')
                ->references('id')
                ->on('mobile_devices')
                ->cascadeOnDelete();

            $table->index(['mobile_device_id', 'expires_at']);
            $table->index(['user_id', 'last_activity_at']);
        });

        Schema::create('mobile_push_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('mobile_device_id')->nullable();
            $table->string('notification_type', 50)->comment('e.g., transaction.received, security.login');
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable()->comment('Additional payload data');
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed', 'read'])->default('pending');
            $table->string('external_id')->nullable()->comment('FCM/APNS message ID');
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('read_at')->nullable();
            $table->timestamps();

            $table->foreign('mobile_device_id')
                ->references('id')
                ->on('mobile_devices')
                ->nullOnDelete();

            $table->index(['user_id', 'status']);
            $table->index(['mobile_device_id', 'status']);
            $table->index(['notification_type', 'created_at']);
            $table->index(['status', 'retry_count']);
        });

        Schema::create('biometric_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('mobile_device_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('challenge', 64)->unique()->comment('Random challenge for signing');
            $table->enum('status', ['pending', 'verified', 'expired', 'failed'])->default('pending');
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('expires_at');
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('mobile_device_id')
                ->references('id')
                ->on('mobile_devices')
                ->cascadeOnDelete();

            $table->index(['mobile_device_id', 'status']);
            $table->index(['challenge', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_challenges');
        Schema::dropIfExists('mobile_push_notifications');
        Schema::dropIfExists('mobile_device_sessions');
        Schema::dropIfExists('mobile_devices');
    }
};
