<?php

namespace App\Http\Controllers;

use App\Models\Collaborator;
use Illuminate\Http\Request;

class CollaboratorController extends Controller
{
    /** Listar todos los colaboradores (solo Admin) */
    public function index()
    {
        $collaborators = Collaborator::all();
        return response()->json($collaborators);
    }

    /** Registrar un nuevo colaborador (cualquier persona puede registrarse) */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:collaborators',
            'reason' => 'required|string',
        ]);

        $collaborator = Collaborator::create([
            'name' => $request->name,
            'email' => $request->email,
            'reason' => $request->reason,
            'subscription_status' => true,
            'status' => 'activo',
        ]);

        return response()->json([
            'message' => 'Colaborador registrado correctamente',
            'collaborator' => $collaborator,
        ], 201);
    }

    /** Actualizar datos del colaborador (solo Admin) */
    public function update(Request $request, $id)
    {
        $collaborator = Collaborator::findOrFail($id);

        $request->validate([
            'subscription_status' => 'in:subscribed,unsubscribed',
            'status' => 'in:activo,inactivo',
        ]);

        $collaborator->update($request->only(['subscription_status', 'status']));

        return response()->json([
            'message' => 'Colaborador actualizado',
            'collaborator' => $collaborator,
        ]);
    }

    /** Desactivar un colaborador en lugar de eliminar (soft delete) */
    public function destroy($id)
    {
        $collaborator = Collaborator::findOrFail($id);
        $collaborator->update(['status' => 'inactivo']);

        return response()->json(['message' => 'Colaborador desactivado']);
    }

    /** Permite que un colaborador se desuscriba del boletín */
    public function unsubscribe(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $collaborator = Collaborator::where('email', $request->email)->first();

        if (!$collaborator) {
            return response()->json(['message' => 'Correo no encontrado'], 404);
        }

        $collaborator->update(['subscription_status' => 'unsubscribed']);

        return response()->json(['message' => 'Te has desuscrito del boletín']);
    }
}
