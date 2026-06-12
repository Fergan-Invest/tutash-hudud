<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $fillable = ['external_id', 'name'];

    public function mahallas()
    {
        return $this->hasMany(Mahalla::class);
    }

    public function streets()
    {
        return $this->hasMany(Street::class);
    }
}
