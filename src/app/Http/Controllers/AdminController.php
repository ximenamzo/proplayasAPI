<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class AdminController extends Controller
{
    /** OBTENER TODOS LOS ADMINS */
    public function index(Request $request)
    {
        if (!in_array($request->user()->role, ['admin', 'node_leader', 'member'])) {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }

        $admins = User::where('role', 'admin')
            ->select('id', 'name', 'email', 'degree', 'postgraduate', 'about')
            ->get();

        return ApiResponse::success('Lista de administradores obtenida', $admins);
    }

    /** OBTENER DETALLES UN SOLO ADMIN (CON ID) */
    public function show($id, Request $request)
    {
        if (!in_array($request->user()->role, ['admin', 'node_leader', 'member'])) {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }

        $admin = User::where('id', $id)->where('role', 'admin')
            ->select('id', 'name', 'email', 'degree', 'postgraduate', 'about')
            ->first();

        if (!$admin) {
            return ApiResponse::notFound('Admin no encontrado', 404);
        }

        return ApiResponse::success('Detalles del administrador', $admin);
    }

    /** EDITAR INFO DEL ADMIN (SOLO ADMIN A SI MISMO) */
    public function update(Request $request, $id)
    {
        $admin = User::where('id', $id)->where('role', 'admin')->first();

        if (!$admin) {
            return ApiResponse::notFound('Admin no encontrado', 404);
        }

        if ($request->user()->id !== $admin->id) {
            return ApiResponse::unauthorized('Unauthorized', 403);
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
            return ApiResponse::unauthenticated('Contraseña incorrecta', 401);
        }

        // Actualizar los datos permitidos
        $admin->update($request->only(['name', 'degree', 'postgraduate', 'about']));

        return ApiResponse::success('Perfil actualizado correctamente', $admin);
    }

    /**
     * 🔹 Eliminar un administrador (solo desde consola)
     */
    public function destroy($id, Request $request)
    {
        if (app()->environment() !== 'local') {
            return ApiResponse::unauthorized('Unauthorized: Solo permitido en desarrollo', 403);
        }

        $admin = User::where('id', $id)->where('role', 'admin')->first();

        if (!$admin) {
            return ApiResponse::notFound('Admin no encontrado', 404);
        }

        $admin->delete();

        return ApiResponse::success('Administrador eliminado correctamente');
    }
}
