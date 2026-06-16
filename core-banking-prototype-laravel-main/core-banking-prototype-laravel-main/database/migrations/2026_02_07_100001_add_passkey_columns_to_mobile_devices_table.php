<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('mobile_devices', function (Blueprint $table) {
            $table->boolean('passkey_enabled')->default(false)->after('biometric_blocked_until');
            $table->text('passkey_credential_id')->nullable()->after('passkey_enabled');
            $table->text('passkey_public_key')->nullable()->after('passkey_credential_id');
            $table->dateTime('passkey_enabled_at')->nullable()->after('passkey_public_key');
        });
    }

    public function down(): void
    {
        Schema::table('mobile_devices', function (Blueprint $table) {
            $table->dropColumn([
                'passkey_enabled',
                'passkey_credential_id',
                'passkey_public_key',
                'passkey_enabled_at',
            ]);
        });
    }
};
