<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class RegistryRequest extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft', 'submitted', 'in_review', 'approved', 'rejected'];
    public const STREET_TYPES = [
        'kocha' => 'Ko‘cha',
        'gostronomik' => 'Gostronomik',
        'turizm' => 'Turizm',
    ];

    protected $fillable = [
        'request_number', 'status', 'created_by', 'updated_by', 'building_cadastr_number',
        'hokimyatga_biriktirilgan_kadastr_raqami', 'owner_type', 'owner_stir_pinfl', 'owner_name',
        'district_id', 'mahalla_id', 'street_id', 'house_number', 'street_type', 'director_name',
        'phone_number', 'area_length', 'area_width', 'calculated_land_area', 'total_area',
        'building_facade_length', 'summer_terrace_sides', 'distance_to_roadway',
        'distance_to_sidewalk', 'usage_purpose', 'activity_type', 'terrace_buildings_available',
        'terrace_buildings_permanent', 'has_permit', 'has_tenant', 'tenant_stir_pinfl',
        'tenant_name', 'tenant_activity_type', 'adjacent_activity_type', 'adjacent_activity_land',
        'adjacent_facilities', 'additional_info', 'latitude', 'longitude', 'polygon_coordinates',
    ];

    protected $casts = [
        'terrace_buildings_available' => 'boolean',
        'terrace_buildings_permanent' => 'boolean',
        'has_permit' => 'boolean',
        'has_tenant' => 'boolean',
        'adjacent_facilities' => 'array',
        'polygon_coordinates' => 'array',
    ];

    protected static function booted(): void
    {
        static::deleting(function (RegistryRequest $request) {
            if ($request->isForceDeleting()) {
                Storage::disk('public')->deleteDirectory("requests/{$request->id}");
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function mahalla()
    {
        return $this->belongsTo(Mahalla::class);
    }

    public function street()
    {
        return $this->belongsTo(Street::class);
    }

    public function images()
    {
        return $this->hasMany(RequestImage::class);
    }

    public function files()
    {
        return $this->hasMany(RequestFile::class);
    }

    public function audits()
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest();
    }
}
