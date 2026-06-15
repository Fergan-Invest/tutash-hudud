<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registry_requests', function (Blueprint $table) {
            $table->boolean('total_area_manual')->default(false)->after('total_area');
        });
    }

    public function down(): void
    {
        Schema::table('registry_requests', function (Blueprint $table) {
            $table->dropColumn('total_area_manual');
        });
    }
};
