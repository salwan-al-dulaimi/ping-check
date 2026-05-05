<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'website_id',
        'status_code',
        'response_time',
        'checked_at',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'response_time' => 'integer',
        'checked_at' => 'datetime',
    ];

    public function website()
    {
        return $this->belongsTo(Website::class);
    }
}
