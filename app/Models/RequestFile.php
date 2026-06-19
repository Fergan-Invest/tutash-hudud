<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestFile extends Model
{
    protected $fillable = ['registry_request_id', 'uploaded_by', 'type', 'path', 'original_name', 'mime', 'size'];

    public function registryRequest()
    {
        return $this->belongsTo(RegistryRequest::class);
    }
}
