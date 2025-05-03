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

    /** 🟠 Reasignar un miembro a otro o nuevo nodo */
    public function reassignNode(Request $request, $id)
    {
        if ($request->user->role !== 'admin') {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }
    
        $request->validate([
            'new_node_id' => 'required|exists:nodes,id'
        ]);
    
        $user = User::find($id);
    
        if (!$user || $user->role !== 'member') {
            return ApiResponse::notFound('Usuario no válido para reasignación', 404);
        }
    
        $newNode = Node::find($request->new_node_id);
    
        // Buscar si ya existe como miembro (aunque esté desactivado)
        $member = Member::where('user_id', $id)->first();
    
        if ($member) {
            $oldNode = Node::find($member->node_id);
            $member->update(['node_id' => $request->new_node_id]);
        } else {
            // Crear nueva membresía
            $lastMember = Member::where('node_id', $newNode->id)->orderBy('id', 'desc')->first();
            $lastCode = $lastMember ? intval(substr($lastMember->member_code, -2)) + 1 : 1;
            $formattedCode = str_pad($lastCode, 2, '0', STR_PAD_LEFT);
    
            $member = Member::create([
                'user_id' => $id,
                'node_id' => $newNode->id,
                'member_code' => strtoupper($newNode->code) . "." . $formattedCode,
                'status' => 'activo',
            ]);
    
            $oldNode = null; // no tenía nodo antes
        }
    
        // Actualizar conteos
        if ($oldNode) Node::where('id', $oldNode->id)->decrement('members_count');
        Node::where('id', $newNode->id)->increment('members_count');
    
        return ApiResponse::success('Miembro reasignado correctamente', [
            'old_node' => $oldNode?->name ?? 'Sin nodo',
            'new_node' => $newNode->name
        ]);
    }

    
    /** 🟠 Activar o desactivar un miembro */
    public function toggleStatus($id, Request $request) {
        $member = Member::find($id);
    
        if (!$member) {
            return ApiResponse::notFound('Miembro no encontrado', 404);
        }
    
        $auth = $request->user();
        
        if (!in_array($auth->role, ['admin', 'node_leader'])) {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }

        // Validar que si es node_leader, solo pueda editar miembros de su nodo
        if ($auth->role === 'node_leader') {
            $authNode = Node::where('leader_id', $auth->sub ?? $auth->id)->first();
            if (!$authNode || $authNode->id !== $member->node_id) {
                return ApiResponse::unauthorized('No autorizado para editar este miembro', 403);
            }
        }

        $member->status = $member->status === 'activo' ? 'inactivo' : 'activo';
        $member->save();

        $user = $member->user;

        return ApiResponse::success('Estado del miembro actualizado correctamente', [
            'id' => $member->id,
            'user_id' => $user->id,
            'node_id' => $member->node_id,
            'member_code' => $member->member_code,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'research_line' => $user->expertise_area,
            'work_area' => $user->research_work,
            'status' => $member->status,
        ]);
    }
    

    /** 🔴 Eliminar a miembro del nodo en el que está */
    public function removeFromNode($id, Request $request) {
        $member = Member::find($id);
    
        if (!$member) {
            return ApiResponse::notFound('Miembro no encontrado', 404);
        }
    
        $auth = $request->user();

        if (!in_array($auth->role, ['admin', 'node_leader'])) {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }

        if ($auth->role === 'node_leader') {
            $authNode = Node::where('leader_id', $auth->sub ?? $auth->id)->first();
            if (!$authNode || $authNode->id !== $member->node_id) {
                return ApiResponse::unauthorized('No autorizado para editar este miembro', 403);
            }
        }

        $nodeId = $member->node_id;

        $member->delete(); // Eliminar "fisica" de la fila en la tabla members

        Node::where('id', $nodeId)->decrement('members_count'); // Decrementar el contador de miembros del nodo

        return ApiResponse::success('Miembro eliminado correctamente', null);
    }


    // Node Leader elimina miembro de su nodo
    /*public function destroy($id, Request $request) {
        $member = Member::find($id);
    
        if (!$member) {
            return ApiResponse::notFound('Miembro no encontrado', 404);
        }
    
        if ($request->user->id !== $member->node->leader_id) {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }
    
        $member->update(['status' => 'inactivo']);
        return ApiResponse::success('Miembro eliminado correctamente', $member);
    } */   
}
