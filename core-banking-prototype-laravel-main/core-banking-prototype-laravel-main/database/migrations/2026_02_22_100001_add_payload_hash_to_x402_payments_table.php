<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('x402_payments', function (Blueprint $table) {
            $table->string('payload_hash', 64)->nullable()->after('payment_payload');
            $table->unique('payload_hash');
        });
    }

    public function down(): void
    {
        Schema::table('x402_payments', function (Blueprint $table) {
            $table->dropUnique(['payload_hash']);
            $table->dropColumn('payload_hash');
        });
    }
};
