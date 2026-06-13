<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Street extends Model
{
    public const TYPES = [
        'kocha' => 'Kōcha',
        'gostronomik' => 'Gostronomik',
        'turizm' => 'Turizm',
    ];

    protected $fillable = ['district_id', 'mahalla_id', 'name', 'type', 'created_by', 'updated_by'];

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function mahalla()
    {
        return $this->belongsTo(Mahalla::class);
    }

    public function audits()
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest();
    }
}
