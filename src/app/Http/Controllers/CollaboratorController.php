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
        $this->middleware(['auth:sanctum', 'role:admin'])->except(['store', 'unsubscribe']);
    }

    /** Listar todos los colaboradores (solo Admin) */
    public function index()
    {
        return response()->json([
            'status' => 200,
            'message' => 'Lista de colaboradores obtenida',
            'data' => Collaborator::all()
        ], 200);
    }

    /** Registrar un nuevo colaborador (cualquier persona puede registrarse) */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:collaborators',
            'reason' => 'required|string',
        ]);
    
        return response()->json([
            'status' => 201,
            'message' => 'Colaborador registrado correctamente',
            'data' => Collaborator::create([
                'name' => $request->name,
                'email' => $request->email,
                'reason' => $request->reason,
                'subscription_status' => true,
                'status' => 'activo',
            ])
        ], 201);
    }

    /** Actualizar datos del colaborador (solo Admin) */
    public function update(Request $request, $id)
    {
        $collaborator = Collaborator::find($id);

        if (!$collaborator) {
            return response()->json([
                'status' => 404,
                'error' => 'Colaborador no encontrado'
            ], 404);
        }

        $request->validate([
            'subscription_status' => 'boolean',
            'status' => 'in:activo,inactivo',
        ]);

        $collaborator->update($request->only(['subscription_status', 'status']));

        return response()->json([
            'status' => 200,
            'message' => 'Colaborador actualizado',
            'data' => $collaborator
        ], 200);
    }

    /** Desactivar un colaborador en lugar de eliminar (soft delete) */
    public function destroy($id)
    {
        $collaborator = Collaborator::find($id);

        if (!$collaborator) {
            return response()->json([
                'status' => 404,
                'error' => 'Colaborador no encontrado'
            ], 404);
        }

        $collaborator->update(['status' => 'inactivo']);

        return response()->json([
            'status' => 200,
            'message' => 'Colaborador desactivado'
        ], 200);
    }

    /** Permite que un colaborador se desuscriba del boletín */
    public function unsubscribe(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $collaborator = Collaborator::where('email', $request->email)->first();

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
