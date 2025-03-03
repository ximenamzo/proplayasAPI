<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Node;

class NodeController extends Controller
{
    // ADMIN: Ver todos los nodos
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
            'message' => 'Lista de nodos obtenida', 
            'data' => Node::all()
        ], 200);
    }

    // Ver la información de un nodo (Publico)
    public function show($id)
    {
        $node = Node::find($id);

        if (!$node) {
            return response()->json([
                'status' => 404,
                'error' => 'Nodo no encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Nodo obtenido',
            'data' => $node
        ], 200);
    }

    // Node Leader: Actualizar datos del nodo
    public function update(Request $request, $id)
    {
        $node = Node::find($id);

        if (!$node) {
            return response()->json([
                'status' => 404,
                'error' => 'Nodo no encontrado'
            ], 404);
        }

        if ($request->user->id !== $node->leader_id) {
            return response()->json([
                'status' => 403,
                'error' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'name' => 'string|max:255',
            'about' => 'string',
            'profile_picture' => 'string|nullable',
            'social_media' => 'json|nullable',
            'status' => 'in:activo,inactivo',
        ]);

        $node->update($request->all());

        return response()->json([
            'status' => 200,
            'message' => 'Nodo actualizado',
            'data' => $node
        ], 200);
    }

    // ADMIN: Eliminar un nodo
    public function destroy(Request $request, $id)
    {
        $node = Node::find($id);

        if (!$node) {
            return response()->json([
                'status' => 404,
                'error' => 'Nodo no encontrado'
            ], 404);
        }

        if ($request->user->role !== 'admin') {
            return response()->json([
                'status' => 403,
                'error' => 'Unauthorized'
            ], 403);
        }

        $node->update(['status' => 'inactivo']);

        return response()->json([
            'status' => 200,
            'message' => 'Nodo desactivado'
        ], 200);
    }
}
