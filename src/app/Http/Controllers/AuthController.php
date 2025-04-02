<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Node;
use App\Models\Member;
use App\Helpers\JWTHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /** Registro de usuarios */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'string|nullable|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,node_leader,member',
            'about' => 'string|nullable',
            'degree' => 'string|nullable|max:255',
            'postgraduate' => 'string|nullable|max:255',
            'expertise_area' => 'string|nullable|max:255',
            'research_work' => 'string|nullable|max:255',
            'profile_picture' => 'string|nullable|max:255',
            'social_media' => 'array|nullable',
        ]);

        // Decodificar contraseña base64
        $decodedPassword = base64_decode($request->password);

        // Crear usuario
        $user = User::create([
            'name'             => $request->name,
            'username'         => $request->username,
            'email'            => $request->email,
            'password'         => Hash::make($decodedPassword),
            'role'             => $request->role,
            'about'            => $request->about,
            'degree'           => $request->degree,
            'postgraduate'     => $request->postgraduate,
            'expertise_area'   => $request->expertise_area,
            'research_work'    => $request->research_work,
            'profile_picture'  => $request->profile_picture,
            'social_media'     => $request->social_media,
            'status'           => 'activo',
        ]);

        // Asignar rol
        $role = Role::where('name', $request->role)->first();
        if ($role) {
            $user->assignRole($role);
        } else {
            return response()->json([
                'status' => 400,
                'error' => 'Role not found'
            ], 400);
        }

        return response()->json([
            'status' => 201,
            'message' => 'Usuario registrado con éxito',
            'data' => $user
        ], 201);
    }



    /** Login */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // Decodificar la contraseña base64 antes de validarla
        $decodedPassword = base64_decode($request->password);

        if (!$user || !Hash::check($decodedPassword, $user->password)) {
            return response()->json([
                'status' => 401,
                'error' => 'Invalid credentials'
            ], 401);
        }

        $token = JWTHandler::createToken($user, $request);

        // Obtener node_code si aplica
        $nodeCode = null;
        $node_id = null;

        if ($user->role === 'node_leader') {
            $nodeCode = Node::where('leader_id', $user->id)->value('code');
        } elseif ($user->role === 'member') {
            $node_id = Member::where('user_id', $user->id)->value('node_id');
            $nodeCode = $node_id ? Node::where('id', $node_id)->value('code') : null; 
        }

        return response()->json([
            'status' => 200,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'role' => $user->role,
                'node_id' => $nodeCode
            ]
        ], 200);
    }



    /** Logout */
    public function logout(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status' => 400, 
                'error' => 'Token not provided'
            ], 400);
        }

        JWTHandler::invalidateToken($token);

        return response()->json([
            'status' => 200,
            'message' => 'Logged out successfully'
        ], 200);
    }

    /** Logout de todas las sesiones */
    public function logoutAll(Request $request)
    {
        JWTHandler::invalidateAllSessions($request->user->sub);

        return response()->json([
            'status' => 200,
            'message' => 'All sessions logged out'
        ], 200);
    }
}
