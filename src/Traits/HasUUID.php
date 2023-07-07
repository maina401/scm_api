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
            //if model has created by field, set it to current user id
            if (in_array('created_by', $model->fillable)) {
                $model->created_by = session()->get('user_id');
            }
        });
    }
}