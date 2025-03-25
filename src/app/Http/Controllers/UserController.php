<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /** 🟢 Obtener todos los usuarios */
    public function index()
    {
        if (app()->environment() !== 'local') {
            return response()->json([
                'status' => 403, 
                'error' => 'Este endpoint solo está disponible en entorno de desarrollo'
            ], 403);
        }

        $users = User::all();

        return response()->json([
            'status' => 200,
            'message' => 'Lista de usuarios obtenida',
            'data' => $users
        ]);
    }

    /** 🔵 Obtener un usuario por ID */
    public function show($id)
    {
        $user = User::where('id', $id)
                    ->whereIn('role', ['node_leader', 'member'])
                    ->select('id', 'name', 'username', 'email', 'role', 'about', 'degree', 'postgraduate', 'expertise_area', 'research_work', 'profile_picture', 'social_media', 'status')
                    ->first();

        if (!$user) {
            return response()->json([
                'status' => 404,
                'error' => 'Usuario no encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'data' => $user
        ]);
    }

    /** 🔵 Obtener un usuario por USERNAME */
    public function showByUsername($username)
    {
        $user = User::where('username', $username)
                    ->whereIn('role', ['node_leader', 'member'])
                    ->select('id', 'name', 'username', 'email', 'role', 'about', 'degree', 'postgraduate', 'expertise_area', 'research_work', 'profile_picture', 'social_media', 'status')
                    ->first();

        if (!$user) {
            return response()->json([
                'status' => 404,
                'error' => 'Usuario no encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'data' => $user
        ]);
    }
}
