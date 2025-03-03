<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Config;
use App\Models\Session;

class JWTHandler
{
    public static function createToken($user, $request = null)
    {
        $payload = [
            'iss' => env('APP_URL'),
            'iat' => time(),
            'exp' => time() + (Config::get('jwt.ttl') * 60)
        ];

        // Verificar si es un usuario registrado o una invitación
        if (isset($user->id)) {
            $payload['sub'] = $user->id;
            $payload['email'] = $user->email;
            $payload['role'] = $user->role;

            // Si es una sesión real, se guarda en BD
            if ($request) {
                $token = JWT::encode($payload, Config::get('jwt.secret'), 'HS256');
                Session::create([
                    'user_id' => $user->id,
                    'token' => $token,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent')
                ]);
                return $token;
            }
        } elseif (isset($user->email)) {
            $payload['name'] = $user->name;
            $payload['email'] = $user->email;
            $payload['role_type'] = $user->role_type;
            $payload['node_type'] = $user->node_type ?? null;

            return JWT::encode($payload, Config::get('jwt.secret'), 'HS256');
        }

        throw new \Exception("Invalid data provided for token generation.");
    }

    public static function decodeToken($token)
    {
        return JWT::decode($token, new Key(Config::get('jwt.secret'), 'HS256'));
    }

    public static function invalidateToken($token)
    {
        // Elimina la sesión de la BD
        return Session::where('token', $token)->delete();
    }

    public static function invalidateAllSessions($userId)
    {
        // Elimina todas las sesiones activas del usuario
        return Session::where('user_id', $userId)->delete();
    }
}
