<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class NodeLeaderController extends Controller
{
    /** 🟢 Node Leader actualiza su propio perfil */
    public function update(Request $request, $id)
    {
        $leader = User::where('id', $id)->where('role', 'node_leader')->first();

        if (!$leader) {
            return ApiResponse::notFound('Node Leader no encontrado', 404);
        }

        if ($request->user()->sub !== $leader->id) {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }

        $request->validate([
            'name' => 'string|max:255',
            'degree' => 'string|max:255|nullable',
            'postgraduate' => 'string|max:255|nullable',
            'about' => 'string|nullable',
            'current_password' => 'required|string'
        ]);

        // Verificar contraseña
        if (!Hash::check(base64_decode($request->current_password), $leader->password)) {
            return ApiResponse::unauthenticated('Contraseña incorrecta', 401);
        }

        $leader->update($request->only(['name', 'degree', 'postgraduate', 'about']));

        return ApiResponse::success('Perfil actualizado correctamente', $leader);
    }

    /** 🔴 Admin elimina (soft delete) a un Node Leader */
    public function destroy($id, Request $request)
    {
        $leader = User::where('id', $id)->where('role', 'node_leader')->first();

        if (!$leader) {
            return ApiResponse::notFound('Node Leader no encontrado', 404);
        }

        if ($request->user()->role !== 'admin') {
            return ApiResponse::unauthorized('Unauthorized: Solo un administrador puede eliminar líderes de nodo', 403);
        }

        $leader->update(['status' => 'inactivo']);

        return ApiResponse::success('Node Leader eliminado correctamente', $leader);
    }
}
