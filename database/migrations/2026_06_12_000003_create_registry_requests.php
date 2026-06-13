<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registry_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->string('status', 30)->default('draft')->index();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('building_cadastr_number')->index();
            $table->string('hokimyatga_biriktirilgan_kadastr_raqami')->nullable()->index();
            $table->string('owner_stir_pinfl', 32)->index();
            $table->string('owner_name')->index();
            $table->foreignId('district_id')->constrained()->restrictOnDelete();
            $table->foreignId('mahalla_id')->constrained()->restrictOnDelete();
            $table->foreignId('street_id')->constrained()->restrictOnDelete();
            $table->string('house_number');
            $table->string('street_type', 40);
            $table->string('director_name');
            $table->string('phone_number', 32)->nullable();
            $table->decimal('area_length', 12, 2);
            $table->decimal('area_width', 12, 2);
            $table->decimal('calculated_land_area', 12, 2);
            $table->decimal('total_area', 12, 2);
            $table->decimal('building_facade_length', 12, 2)->nullable();
            $table->decimal('summer_terrace_sides', 12, 2)->nullable();
            $table->decimal('distance_to_roadway', 12, 2);
            $table->decimal('distance_to_sidewalk', 12, 2);
            $table->string('usage_purpose', 80);
            $table->string('activity_type');
            $table->boolean('terrace_buildings_available');
            $table->boolean('terrace_buildings_permanent');
            $table->boolean('has_permit');
            $table->boolean('has_tenant')->default(false);
            $table->string('tenant_stir_pinfl', 32)->nullable()->index();
            $table->string('tenant_name')->nullable();
            $table->string('tenant_activity_type')->nullable();
            $table->string('adjacent_activity_type')->nullable();
            $table->decimal('adjacent_activity_land', 12, 2);
            $table->json('adjacent_facilities');
            $table->text('additional_info')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->json('polygon_coordinates');
            $table->timestamps();
            $table->index(['district_id', 'mahalla_id', 'street_id']);
            $table->index(['created_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registry_requests');
    }
};
