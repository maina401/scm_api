<?php

namespace Leaf\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Leaf\Traits\HasUUID;

class Event extends Model
{

    protected $table = 'events';
    protected $fillable = [
        'title',
        'description',
        'created_by',
        "meta"
    ];
    //belongs to user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'created_by');
    }
}