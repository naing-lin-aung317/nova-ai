<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('idempotency_key')->nullable();
            $table->string('content_type');
            $table->string('language');
            $table->string('length');
            $table->foreignId('built_in_tone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('custom_tone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('topic', 1000);
            $table->string('audience')->nullable();
            $table->string('keywords')->nullable();
            $table->string('call_to_action')->nullable();
            $table->longText('output_text')->nullable();
            $table->string('status');
            $table->string('failure_reason')->nullable();
            $table->string('model')->nullable();
            $table->timestamp('stream_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('regenerated_from_id')->nullable()->constrained('generation_requests')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'idempotency_key']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generation_requests');
    }
};
