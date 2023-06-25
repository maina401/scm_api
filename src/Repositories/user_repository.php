<?php
namespace Leaf\Repositories;
use Leaf\Helpers\Validator;
use Leaf\Models\User;

class user_repository
{
    public function list_users($data): array
    {
        $users = User::all();
        return $users->toArray();
    }

    /**
     * @throws \Exception
     */
    public function get_user($data)
    {
        Validator::make($data, [
            'id' => 'required|uuid',
        ]);
        return User::findOrFail($data->id);
    }

/**
     * @throws \Exception
     */


}
