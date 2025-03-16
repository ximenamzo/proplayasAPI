<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Node;
use App\Models\Member;

class MemberController extends Controller
{
    public function index() {
        return response()->json([
            'status' => 200,
            'message' => 'Lista de miembros obtenida',
            'data' => Member::all()
        ]);
    }
    
    public function show($id) {
        $member = Member::find($id);
        if (!$member) {
            return response()->json(['error' => 'Miembro no encontrado'], 404);
        }
        return response()->json($member);
    }

    public function update(Request $request, $id)
    {
        $member = User::where('id', $id)->where('role', 'member')->first();

        if (!$member) {
            return response()->json(['error' => 'Miembro no encontrado'], 404);
        }

        if ($request->user->id !== $member->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'name' => 'string|max:255',
            'expertise_area' => 'string|nullable',
            'research_work' => 'string|nullable',
            'profile_picture' => 'string|nullable',
            'social_media' => 'json|nullable'
        ]);

        $member->update($request->all());

        return response()->json(['message' => 'Perfil actualizado', 'data' => $member]);
    }

    public function reassignNode(Request $request, $id)
    {
        if ($request->user->role !== 'admin') {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'new_node_id' => 'required|exists:nodes,id'
        ]);

        $member = Member::where('user_id', $id)->first();
        
        if (!$member) {
            return response()->json(['error' => 'Miembro no encontrado'], 404);
        }

        $oldNode = Node::find($member->node_id);
        $newNode = Node::find($request->new_node_id);

        $member->update(['node_id' => $request->new_node_id]);

        return response()->json([
            'message' => 'Miembro reasignado correctamente',
            'old_node' => $oldNode->name,
            'new_node' => $newNode->name
        ]);
    }
    
    // Node Leader elimina miembro de su nodo
    public function destroy($id, Request $request) {
        $member = Member::find($id);
    
        if (!$member) {
            return response()->json(['error' => 'Miembro no encontrado'], 404);
        }
    
        if ($request->user->id !== $member->node->leader_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
    
        $member->update(['status' => 'inactivo']);
        return response()->json(['message' => 'Miembro eliminado']);
    }    
}
