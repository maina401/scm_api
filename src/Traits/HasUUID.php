<?php

namespace Leaf\Traits;

use Illuminate\Support\Str;

trait HasUUID
{
    //boot method automatically called by laravel
    public static function bootHasUUID()
    {
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }
}