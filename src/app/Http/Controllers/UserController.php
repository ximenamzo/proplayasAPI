<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /** Obtener todos los usuarios */
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

    /** Obtener un solo usuario segun su ID */
    public function show($id)
    {
        if (app()->environment() !== 'local') {
            return response()->json([
                'status' => 403, 
                'error' => 'Este endpoint solo está disponible en entorno de desarrollo'
            ], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 404, 
                'error' => 'Usuario no encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Usuario encontrado',
            'data' => $user
        ]);
    }
}
