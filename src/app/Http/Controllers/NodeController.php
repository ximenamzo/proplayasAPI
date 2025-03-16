<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Node;
use App\Models\Member;

class NodeController extends Controller
{
    public function index() {
        return response()->json([
            'status' => 200,
            'message' => 'Lista de nodos obtenida',
            'data' => Node::all()
        ]);
    }
    
    public function show($id) {
        $node = Node::find($id);
        if (!$node) {
            return response()->json(['error' => 'Nodo no encontrado'], 404);
        }
        return response()->json($node);
    }

    public function update(Request $request, $id) {
        $node = Node::find($id);
        
        if (!$node) {
            return response()->json(['error' => 'Nodo no encontrado'], 404);
        }
    
        if ($request->user->id !== $node->leader_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
    
        $request->validate([
            'name' => 'string|max:255',
            'about' => 'string|nullable',
            'profile_picture' => 'string|nullable',
            'social_media' => 'json|nullable'
        ]);
    
        $node->update($request->all());
    
        return response()->json(['message' => 'Nodo actualizado', 'node' => $node]);
    }    
    
    // El admin puede cambiar al líder de un nodo
    public function reassignLeader(Request $request, $id) {
        $request->validate([
            'new_leader_id' => 'required|exists:users,id'
        ]);
    
        $node = Node::findOrFail($id);
        $oldLeader = User::find($node->leader_id);
        $newLeader = User::find($request->new_leader_id);
    
        if ($oldLeader) {
            $oldLeader->role = 'member'; // El antiguo líder ahora es miembro
            $oldLeader->save();
        }
    
        $newLeader->role = 'node_leader';
        $newLeader->save();
    
        $node->leader_id = $newLeader->id;
        $node->save();
    
        return response()->json([
            'message' => 'Líder del nodo actualizado con éxito',
            'node' => $node
        ]);
    }
    
    // Soft delete de un nodo
    public function destroy($id) {
        $node = Node::find($id);
        if (!$node) {
            return response()->json(['error' => 'Nodo no encontrado'], 404);
        }
        $node->update(['status' => 'inactivo']);
        return response()->json(['message' => 'Nodo desactivado']);
    }    
}
