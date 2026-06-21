<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mahalla extends Model
{
    protected $fillable = ['district_id', 'name'];

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function streets()
    {
        return $this->hasMany(Street::class);
    }

    public function audits()
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest();
    }
}
