<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('registry_requests')) {
            return;
        }

        match (DB::getDriverName()) {
            'mysql' => DB::statement('ALTER TABLE registry_requests MODIFY hokimyatga_biriktirilgan_kadastr_raqami VARCHAR(255) NULL'),
            'pgsql' => DB::statement('ALTER TABLE registry_requests ALTER COLUMN hokimyatga_biriktirilgan_kadastr_raqami DROP NOT NULL'),
            default => null,
        };
    }

    public function down(): void
    {
        if (! Schema::hasTable('registry_requests')) {
            return;
        }

        match (DB::getDriverName()) {
            'mysql' => DB::statement("UPDATE registry_requests SET hokimyatga_biriktirilgan_kadastr_raqami = '' WHERE hokimyatga_biriktirilgan_kadastr_raqami IS NULL"),
            'pgsql' => DB::statement("UPDATE registry_requests SET hokimyatga_biriktirilgan_kadastr_raqami = '' WHERE hokimyatga_biriktirilgan_kadastr_raqami IS NULL"),
            default => null,
        };

        match (DB::getDriverName()) {
            'mysql' => DB::statement('ALTER TABLE registry_requests MODIFY hokimyatga_biriktirilgan_kadastr_raqami VARCHAR(255) NOT NULL'),
            'pgsql' => DB::statement('ALTER TABLE registry_requests ALTER COLUMN hokimyatga_biriktirilgan_kadastr_raqami SET NOT NULL'),
            default => null,
        };
    }
};
