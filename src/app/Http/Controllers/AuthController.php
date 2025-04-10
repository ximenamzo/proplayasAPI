<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Node;
use App\Models\Member;
use App\Helpers\ApiResponse;
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
            return ApiResponse::error('Role not found', 400);
        }

        return ApiResponse::created('Usuario registrado con éxito', $user);
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
            return ApiResponse::unauthenticated('Invalid credentials', 401);
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

        return ApiResponse::success('Login successful', [
            'token' => $token,
            'role' => $user->role,
            'node_id' => $nodeCode
        ]);
    }



    /** Logout */
    public function logout(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return ApiResponse::error('Token not provided', 400);
        }

        JWTHandler::invalidateToken($token);

        return ApiResponse::success('Logged out successfully');
    }

    /** Logout de todas las sesiones */
    public function logoutAll(Request $request)
    {
        JWTHandler::invalidateAllSessions($request->user->sub);

        return ApiResponse::success('All sessions logged out');
    }
}
