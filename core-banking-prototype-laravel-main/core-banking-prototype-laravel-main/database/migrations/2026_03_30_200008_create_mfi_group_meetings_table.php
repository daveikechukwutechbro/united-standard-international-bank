<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('mfi_group_meetings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('group_id');
            $table->date('meeting_date');
            $table->integer('attendees_count')->default(0);
            $table->integer('total_members')->default(0);
            $table->text('minutes')->nullable();
            $table->date('next_meeting_date')->nullable();
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('mfi_groups')->cascadeOnDelete();
            $table->index('group_id');
            $table->index('meeting_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfi_group_meetings');
    }
};
