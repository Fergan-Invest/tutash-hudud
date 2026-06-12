<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestImage extends Model
{
    protected $fillable = ['registry_request_id', 'uploaded_by', 'path', 'original_name', 'mime', 'size', 'sha256'];

    public function registryRequest()
    {
        return $this->belongsTo(RegistryRequest::class);
    }
}
