<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->integer('amount');
            $table->unsignedInteger('available_balance_after');
            $table->unsignedInteger('reserved_balance_after');
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('anonymised_at')->nullable();
            $table->timestamps();

            $table->index(['credit_wallet_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
