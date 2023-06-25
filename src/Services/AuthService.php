<?php

namespace Leaf\Services;

use Leaf\Helpers\ApiException;
use Leaf\Helpers\Validator;
use Leaf\Models\JWT;
use Leaf\Models\User;

class AuthService
{

    //user registration
    /**
     * @throws \Exception
     */
    public function register($data): User
    {
        Validator::make($data, [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|valid_phone|unique:users,phone',
            'password' => 'required|min:6|max:20|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
        ]);
        return User::create((array)$data);
    }
    /**
     * @throws \Exception
     */
    public function merchant_auth($merchant_key, $merchant_secret): JWT
    {
        try {
            $user = User::findMerchant($merchant_key);
        } catch (\Exception $e) {
            throw new ApiException("User not found or Invalid Credentials");
        }

        if (!password_verify($merchant_secret, $user->password)) {
            throw new ApiException("Invalid Credentials");
        }
        //create a new token
        return $this->createToken($user);
    }

    /**
     * @throws \Exception
     */
    private function createToken(User $user): JWT
    {
        $token = new JWT();
        $token->user_id = $user->id;
        $token->token = bin2hex(random_bytes(64));
        $token->ip_address = $_SERVER['REMOTE_ADDR'];
        $token->user_agent = $_SERVER['HTTP_USER_AGENT'];
        $token->payload = json_encode(['user_id' => $user->id, 'iat' => time()]);
        $token->last_activity = time();
        $token->save();
        return $token;
    }

    /**
     * @throws \Exception
     */
    public function merchant_validation(User $user, JWT $jwt): bool
    {
        if ($jwt->user_id !== $user->id) {
            throw new ApiException("Invalid Credentials");
        }
        if ($jwt->ip_address !== $_SERVER['REMOTE_ADDR']) {
            throw new ApiException("Invalid Credentials");
        }
        if ($jwt->user_agent !== $_SERVER['HTTP_USER_AGENT']) {
            throw new ApiException("Invalid Credentials");
        }
        if (time() - $jwt->last_activity > 3600) {
            throw new ApiException("Invalid Credentials");
        }
        return true;
    }
}
