<?php

namespace App\Http\Controllers;

use App\Models\Collaborator;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class CollaboratorController extends Controller
{
    public function __construct()
    {
        // Los administradores solo gestionan colaboradores   
        // Ellos se suscriben o desuscriben por su cuenta
        //$this->middleware(['auth:sanctum', 'role:admin'])->except(['store', 'unsubscribe']);
        $this->middleware(['jwt.auth'])->only(['index', 'update', 'destroy']);
    }

    /** 🔵 Listar todos los colaboradores (solo Admin) */
    public function index(Request $request)
    {
        if ($request->user->role !== 'admin') {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }

        return ApiResponse::success('Lista de colaboradores obtenida', Collaborator::all());
    }

    /** 🟡 Registrar un nuevo colaborador (cualquier persona puede registrarse) */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:collaborators',
            'reason' => 'required|string',
        ]);

        $collaborator = Collaborator::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'reason' => $validated['reason'],
            'subscription_status' => true,
            'status' => 'activo',
        ]);

        return ApiResponse::created('Colaborador registrado correctamente', $collaborator);
    }

    /** 🟠 Actualizar datos del colaborador (solo Admin) */
    public function update(Request $request, $id)
    {
        $collaborator = Collaborator::find($id);

        if (!$collaborator) {
            return ApiResponse::notFound('Colaborador no encontrado', 404);
        }

        if ($request->user->role !== 'admin') {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }

        $validated = $request->validate([
            'subscription_status' => 'boolean',
            'status' => 'in:activo,inactivo',
        ]);

        $collaborator->update($validated);

        return ApiResponse::success('Datos de colaborador actualizados correctamente', $collaborator);
    }

    /** 🔴 Desactivar un colaborador en lugar de eliminar (soft delete) */
    public function destroy(Request $request, $id)
    {
        $collaborator = Collaborator::find($id);

        if (!$collaborator) {
            return ApiResponse::notFound('Colaborador no encontrado', 404);
        }

        if ($request->user->role !== 'admin') {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }

        $collaborator->update(['status' => 'inactivo']);

        return ApiResponse::success('Colaborador desactivado correctamente', $collaborator);
    }

    /** 🟢 Permite que un colaborador se desuscriba del boletín */
    public function unsubscribe(Request $request)
    {
        $validated = $request->validate(['email' => 'required|email']);

        $collaborator = Collaborator::where('email', $validated['email'])->first();

        if (!$collaborator) {
            return ApiResponse::notFound('Correo no encontrado', 404);
        }

        $collaborator->update(['subscription_status' => false]);

        return ApiResponse::success('Te has desuscrito del boletín', $collaborator);
    }
}
