<?php

namespace Leaf\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Leaf\Traits\HasUUID;

class ItemEvent extends Model
{

    protected $table = 'item_events';
    //belongs to user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'created_by');
    }
}