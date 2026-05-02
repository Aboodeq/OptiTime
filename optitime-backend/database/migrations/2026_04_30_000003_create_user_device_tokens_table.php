<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_device_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('fcm_token');
            $table->char('fcm_token_sha256', 64);
            $table->string('device_label', 80)->nullable();
            $table->string('platform', 30)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'fcm_token_sha256'], 'user_device_tokens_user_token_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_device_tokens');
    }
};
