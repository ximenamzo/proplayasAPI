<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Config;

class JWTHandler
{
    public static function createToken($user)
    {
        $payload = [
            'iss' => env('APP_URL'), 
            'sub' => $user->id, 
            'email' => $user->email,
            'role' => $user->role,
            'iat' => time(), 
            'exp' => time() + Config::get('jwt.ttl')
        ];

        return JWT::encode($payload, Config::get('jwt.secret'), 'HS256');
    }

    public static function decodeToken($token)
    {
        return JWT::decode($token, new Key(Config::get('jwt.secret'), 'HS256'));
    }
}
