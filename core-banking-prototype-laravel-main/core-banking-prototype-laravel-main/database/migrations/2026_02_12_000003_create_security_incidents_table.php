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
        Schema::create('security_incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->nullable()->index();
            $table->string('title');
            $table->text('description');
            $table->string('severity')->index(); // critical, high, medium, low
            $table->string('status')->default('open')->index(); // open, investigating, mitigating, resolved, closed
            $table->json('timeline')->nullable(); // array of {timestamp, action, actor, notes}
            $table->text('resolution')->nullable();
            $table->json('affected_systems')->nullable();
            $table->string('reported_by')->nullable();
            $table->string('assigned_to')->nullable();
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('postmortem')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_incidents');
    }
};
