<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ping extends Model
{
    protected $fillable = ['user_id', 'website_address'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
