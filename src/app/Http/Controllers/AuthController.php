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
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,node_leader,member',
        ]);

        // Decodificar la contraseña base64
        $decodedPassword = base64_decode($request->password);

        // Crear usuario con contraseña hasheada
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($decodedPassword),
            'role' => $request->role,
            'status' => 'activo',
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
            'message' => 'User registered successfully', 
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

        // Obtener node_id si aplica
        $node_id = null;
        if ($user->role === 'node_leader') {
            $node_id = Node::where('leader_id', $user->id)->value('id');
        } elseif ($user->role === 'member') {
            $node_id = Member::where('user_id', $user->id)->value('node_id');
        }

        return response()->json([
            'status' => 200,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'role' => $user->role,
                'node_id' => $node_id
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
