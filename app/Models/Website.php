<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Website extends Model
{
    use HasFactory;

    public const STATUS_ONLINE = 'online';
    public const STATUS_OFFLINE = 'offline';

    protected $fillable = [
        'user_id',
        'name',
        'url',
        'check_interval',
        'last_checked_at',
        'status',
        'status_code',
    ];

    protected $casts = [
        'check_interval' => 'integer',
        'last_checked_at' => 'datetime',
        'status_code' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function logs()
    {
        return $this->hasMany(WebsiteLog::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
