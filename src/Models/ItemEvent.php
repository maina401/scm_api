<?php

namespace Leaf\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Leaf\Traits\HasUUID;

class   ItemEvent extends Model
{

    protected $table = 'item_events';
    protected $casts = [
        'item_id' => 'string',
        'event_id' => 'string',
        'created_by' => 'string',
    ];
    protected $fillable = [
        'item_id',
        'event_id',
        'created_by',
        'value',
        "meta"
    ];
    //belongs to user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'created_by');
    }

    //belongs to item
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class,'item_id');
    }
    //belongs to event
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class,'event_id');
    }
}