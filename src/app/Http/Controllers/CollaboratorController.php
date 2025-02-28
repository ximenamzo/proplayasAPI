<?php

namespace App\Http\Controllers;

use App\Models\Collaborator;
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
            return response()->json([
                'status' => 403,
                'error' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Lista de colaboradores obtenida',
            'data' => Collaborator::all()
        ], 200);
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

        return response()->json([
            'status' => 201,
            'message' => 'Colaborador registrado correctamente',
            'data' => $collaborator
        ], 201);
    }

    /** 🟠 Actualizar datos del colaborador (solo Admin) */
    public function update(Request $request, $id)
    {
        $collaborator = Collaborator::find($id);

        if (!$collaborator) {
            return response()->json([
                'status' => 404,
                'error' => 'Colaborador no encontrado'
            ], 404);
        }

        if ($request->user->role !== 'admin') {
            return response()->json([
                'status' => 403,
                'error' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'subscription_status' => 'boolean',
            'status' => 'in:activo,inactivo',
        ]);

        $collaborator->update($validated);

        return response()->json([
            'status' => 200,
            'message' => 'Colaborador actualizado',
            'data' => $collaborator
        ], 200);
    }

    /** 🔴 Desactivar un colaborador en lugar de eliminar (soft delete) */
    public function destroy(Request $request, $id)
    {
        $collaborator = Collaborator::find($id);

        if (!$collaborator) {
            return response()->json([
                'status' => 404,
                'error' => 'Colaborador no encontrado'
            ], 404);
        }

        if ($request->user->role !== 'admin') {
            return response()->json([
                'status' => 403,
                'error' => 'Unauthorized'
            ], 403);
        }

        $collaborator->update(['status' => 'inactivo']);

        return response()->json([
            'status' => 200,
            'message' => 'Colaborador desactivado'
        ], 200);
    }

    /** 🟢 Permite que un colaborador se desuscriba del boletín */
    public function unsubscribe(Request $request)
    {
        $validated = $request->validate(['email' => 'required|email']);

        $collaborator = Collaborator::where('email', $validated['email'])->first();

        if (!$collaborator) {
            return response()->json([
                'status' => 404,
                'error' => 'Correo no encontrado'
            ], 404);
        }

        $collaborator->update(['subscription_status' => false]);

        return response()->json([
            'status' => 200,
            'message' => 'Te has desuscrito del boletín'
        ], 200);
    }
}
