<?php

use Database\Seeders\CreditPackageSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('credits');
            $table->unsignedInteger('price_cents');
            $table->string('currency', 3)->default('usd');
            $table->boolean('is_visible')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        (new CreditPackageSeeder)->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_packages');
    }
};
