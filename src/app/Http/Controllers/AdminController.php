<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class AdminController extends Controller
{
    /** OBTENER TODOS LOS ADMINS */
    public function index(Request $request)
    {
        if (!in_array($request->user()->role, ['admin', 'node_leader', 'member'])) {
            return response()->json([
                'status' => 403,
                'error' => 'Unauthorized'
            ], 403);
        }

        $admins = User::where('role', 'admin')
            ->select('id', 'name', 'email', 'degree', 'postgraduate', 'about')
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'Lista de administradores obtenida',
            'data' => $admins
        ]);
    }

    /** OBTENER DETALLES UN SOLO ADMIN (CON ID) */
    public function show($id, Request $request)
    {
        if (!in_array($request->user()->role, ['admin', 'node_leader', 'member'])) {
            return response()->json([
                'status' => 403, 
                'error' => 'Unauthorized'
            ], 403);
        }

        $admin = User::where('id', $id)->where('role', 'admin')
            ->select('id', 'name', 'email', 'degree', 'postgraduate', 'about')
            ->first();

        if (!$admin) {
            return response()->json([
                'status' => 404, 
                'error' => 'Admin no encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Detalles del administrador',
            'data' => $admin
        ]);
    }

    /** EDITAR INFO DEL ADMIN (SOLO ADMIN A SI MISMO) */
    public function update(Request $request, $id)
    {
        $admin = User::where('id', $id)->where('role', 'admin')->first();

        if (!$admin) {
            return response()->json([
                'status' => 404, 
                'error' => 'Admin no encontrado'
            ], 404);
        }

        if ($request->user()->id !== $admin->id) {
            return response()->json([
                'status' => 403, 
                'error' => 'No autorizado'
            ], 403);
        }

        $request->validate([
            'name' => 'string|max:255',
            'degree' => 'string|max:255|nullable',
            'postgraduate' => 'string|max:255|nullable',
            'about' => 'string|nullable',
            'current_password' => 'required|string'
        ]);

        // Decodificar la contraseña base64
        $decodedPassword = base64_decode($request->current_password);

        // Verificar que la contraseña actual es correcta
        if (!Hash::check($decodedPassword, $admin->password)) {
            return response()->json([
                'status' => 401, 
                'error' => 'Contraseña incorrecta'
            ], 401);
        }

        // Actualizar los datos permitidos
        $admin->update($request->only(['name', 'degree', 'postgraduate', 'about']));

        return response()->json([
            'status' => 200,
            'message' => 'Perfil actualizado correctamente',
            'data' => $admin
        ]);
    }

    /**
     * 🔹 Eliminar un administrador (solo desde consola)
     */
    public function destroy($id, Request $request)
    {
        if (app()->environment() !== 'local') {
            return response()->json([
                'status' => 403, 
                'error' => 'Solo permitido en desarrollo'
            ], 403);
        }

        $admin = User::where('id', $id)->where('role', 'admin')->first();

        if (!$admin) {
            return response()->json(['status' => 404, 'error' => 'Admin no encontrado'], 404);
        }

        $admin->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Administrador eliminado correctamente'
        ]);
    }
}
