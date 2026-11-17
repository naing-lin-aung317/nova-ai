<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->timestamp('fulfilled_by_admin_at')->nullable()->after('paid_at');
            $table->foreignId('fulfilled_by_user_id')->nullable()->after('fulfilled_by_admin_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fulfilled_by_user_id');
            $table->dropColumn('fulfilled_by_admin_at');
        });
    }
};
