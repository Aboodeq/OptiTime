<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_generation_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('queued')->index();
            $table->string('algorithm', 20);
            $table->foreignUuid('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->json('request_payload');
            $table->json('result_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_generation_jobs');
    }
};
