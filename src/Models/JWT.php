<?php

namespace Leaf\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Leaf\Traits\HasUUID;

class JWT extends Model
{

    protected $table = 'jwt';
    protected $fillable = [
        'user_id',
        'token',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
    ];

    protected $hidden = [
        'password',
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = password_hash($value, PASSWORD_DEFAULT);
    }

    //belongs to user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}