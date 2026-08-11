<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registry_requests', function (Blueprint $table) {
            $table->boolean('contract_concluded')->default(false);
            $table->boolean('customer_signed')->default(false);
            $table->boolean('payment_paid')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('registry_requests', function (Blueprint $table) {
            $table->dropColumn(['contract_concluded', 'customer_signed', 'payment_paid']);
        });
    }
};
