<?php

namespace Leaf\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;
use Leaf\Traits\HasUUID;

class Model extends \Illuminate\Database\Eloquent\Model
{
    use HasUUID, SoftDeletes;

    //primary key is not auto incrementing
    public $incrementing = false;
    //primary key is a string
    public mixed $meta;
    protected $fillable=[
        "meta",
    ];
    protected $hidden = [
        'password',
        'merchant_secret',
        'created_at',
        "created_by",
        'updated_at',
        'deleted_at'
    ];
    protected $keyType = 'string';
    protected static function boot()
    {
        parent::boot();
        static::setEventDispatcher(new Dispatcher());
        static::bootHasUUID();
    }
    
    //set get meta attribute
    public function getMetaAttribute($value)
    {
        try {
            return json_decode($value);
        }
        catch (\Exception $e) {
            return $value;
        }
    }
    
    //set set meta attribute
    public function setMetaAttribute($value)
    {
        $this->attributes['meta'] = json_encode($value);
    }

}