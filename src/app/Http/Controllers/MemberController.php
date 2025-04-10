<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Node;
use App\Models\Member;
use App\Helpers\ApiResponse;

class MemberController extends Controller
{
    public function index() {
        return ApiResponse::success('Lista de miembros obtenida', Member::all());
    }
    
    public function show($id) {
        $member = Member::find($id);
        if (!$member) {
            return ApiResponse::notFound('Miembro no encontrado', 404);
        }
        return response()->json($member);
    }

    public function update(Request $request, $id)
    {
        $member = User::where('id', $id)->where('role', 'member')->first();

        if (!$member) {
            return ApiResponse::notFound('Miembro no encontrado', 404);
        }

        if ($request->user->id !== $member->id) {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }

        $request->validate([
            'name' => 'string|max:255',
            'expertise_area' => 'string|nullable',
            'research_work' => 'string|nullable',
            'profile_picture' => 'string|nullable',
            'social_media' => 'json|nullable'
        ]);

        $member->update($request->all());

        return ApiResponse::success('Perfil actualizado correctamente', $member);
    }

    public function reassignNode(Request $request, $id)
    {
        if ($request->user->role !== 'admin') {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }

        $request->validate([
            'new_node_id' => 'required|exists:nodes,id'
        ]);

        $member = Member::where('user_id', $id)->first();
        
        if (!$member) {
            return ApiResponse::notFound('Miembro no encontrado', 404);
        }

        $oldNode = Node::find($member->node_id);
        $newNode = Node::find($request->new_node_id);

        $member->update(['node_id' => $request->new_node_id]);

        return ApiResponse::success('Miembro reasignado correctamente', [
            'old_node' => $oldNode->name,
            'new_node' => $newNode->name
        ]);
    }
    
    // Node Leader elimina miembro de su nodo
    public function destroy($id, Request $request) {
        $member = Member::find($id);
    
        if (!$member) {
            return ApiResponse::notFound('Miembro no encontrado', 404);
        }
    
        if ($request->user->id !== $member->node->leader_id) {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }
    
        $member->update(['status' => 'inactivo']);
        return ApiResponse::success('Miembro eliminado correctamente', $member);
    }    
}
