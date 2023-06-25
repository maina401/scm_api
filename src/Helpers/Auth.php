<?php


namespace Leaf\Helpers;

use Leaf\Models\User;

class Auth
{
    public static function user(): \Leaf\Models\Model|array
    {
       return User::findOrFail(session()->get("auth_user"));
    }

}
