<?php

namespace Leaf\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Leaf\Traits\HasUUID;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'phone',
        'status',
        'created_at',
        'updated_at',
    ];


    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = password_hash($value, PASSWORD_DEFAULT);
    }

    public function setMerchantSecretAttribute($value)
    {
        $this->attributes['merchant_secret'] = password_hash($value, PASSWORD_DEFAULT);
    }

    //query scope findMerchant, which finds by email, phone or id
    public function scopeFindMerchant($query, $merchant_key)
    {
        $query->where('email', $merchant_key)
           ->orWhere('phone', $merchant_key);
        if (Str::isUuid($merchant_key)) {
            $query->orWhere('id', $merchant_key);
        }

        return $query->firstOrFail();
    }

    //has many items
    public function items(): HasMany
    {
        return $this->hasMany(Item::class,'created_by');
    }

    //has many events
    public function events(): HasMany
    {
        return $this->hasMany(Event::class,'created_by');
    }

}
