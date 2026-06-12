<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registry_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('registry_requests', 'owner_type')) {
                $table->string('owner_type', 20)->default('yuridik')->after('hokimyatga_biriktirilgan_kadastr_raqami')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('registry_requests', function (Blueprint $table) {
            if (Schema::hasColumn('registry_requests', 'owner_type')) {
                $table->dropColumn('owner_type');
            }
        });
    }
};
