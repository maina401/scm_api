<?php

namespace Leaf\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Leaf\Traits\HasUUID;

class Item extends Model
{

    protected $table = 'items';
    protected $fillable = [
        'name',
        'description',
        'price',
        'quantity',
        'created_at',
        'updated_at',
        "meta"
    ];
    //belongs to user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'created_by');
    }
    //has many events
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class,'item_events','item_id','event_id');
    }
}