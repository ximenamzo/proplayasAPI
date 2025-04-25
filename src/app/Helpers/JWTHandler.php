<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Config;
use App\Models\Session;
use App\Models\Node;

class JWTHandler
{
    public static function createToken($user, $request = null, $isInvitation = false)
    {
        $payload = [
            'iss' => env('APP_URL', 'http://localhost'),
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

                // 🔹 INVALIDAR SESIONES PREVIAS DE MISMO USUARIO + MISMA IP Y USER AGENT
                Session::where('user_id', $user->id)
                    ->where('ip_address', $request->ip())
                    ->where('user_agent', $request->header('User-Agent'))
                    ->delete();

                // 🔹 CREAN NUEVA SESIÓN
                Session::create([
                    'user_id' => $user->id,
                    'token' => $token,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent')
                ]);

                return $token;
            }
            
        // Si es un token de invitacion
        } elseif ($isInvitation) {
            $payload['name'] = $user->name;
            $payload['email'] = $user->email;
            $payload['role_type'] = $user->role_type;

            // Solo agregar `node_type` si es un `node_leader`
            if ($user->role_type === 'node_leader' && isset($user->node_type)) {
                $payload['node_type'] = $user->node_type;
            }
            
            // Solo agregar `node_id` si es un `member`
            if ($user->role_type === 'member' && isset($user->node_id)) {
                $payload['node_id'] = $user->node_id;
            }

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
