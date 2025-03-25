<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Node;
use App\Models\Member;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Exception;

class NodeController extends Controller
{
    /** 🟢 Ver todos los nodos (público) */
    public function index() {
        $nodes = Node::where('status', 'activo')
            ->select('id', 'code', 'type', 'name', 
                    'city', 'country', 'members_count', 'joined_in')
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'Lista de nodos obtenida',
            'data' => $nodes
        ]);
    }
    
    /** 🔵 Ver perfil de un nodo (público) */
    public function show($id)
    {
        $node = Node::with(['leader:id,name,email,degree,postgraduate'])
                    ->find($id); 

        if (!$node) {
            return response()->json([
                'status' => 404,
                'error' => 'Nodo no encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Detalle del nodo obtenido correctamente',
            'data' => $node
        ]);
    }

    /** 🔵 Ver perfil de un nodo según su CÓDIGO (público) */ 
    public function showByCode($code)
    {
        $node = Node::with(['leader:id,name,email,degree,postgraduate'])
                    ->where('code', $code)
                    ->first();

        if (!$node) {
            return response()->json([
                'status' => 404,
                'error' => 'Nodo no encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Detalle del nodo obtenido correctamente',
            'data' => $node
        ]);
    }


    /** 🟠 Node leader edita su nodo */
    public function update(Request $request, $id)
    {
        Log::info("Intentando actualizar nodo ID: $id");
        $node = Node::find($id);

        if (!$node) {
            Log::warning("Nodo no encontrado con ID: $id");
            return response()->json([
                'status' => 404, 
                'error' => 'Nodo no encontrado'
            ], 404);
        }

        $user = $request->user();
        Log::info("Usuario autenticado:", (array) $user);

        if (!$user || !isset($user->id) || !isset($user->role)) {
            Log::error("No se pudo detectar el rol del usuario");
            return response()->json([
                'status' => 401, 
                'error' => 'Usuario no autenticado correctamente'
            ], 401);
        }
    
        if ($user->role !== 'node_leader') {
            Log::warning("Usuario con ID {$user->id} no es node_leader, es {$user->role}");
            return response()->json([
                'status' => 403, 
                'error' => 'No autorizado'
            ], 403);
        }
        
        if ($user->id != $node->leader_id) {
            Log::warning("Usuario con ID {$user->id} no es el líder del nodo ID $id");
            return response()->json([
                'status' => 403, 
                'error' => 'No autorizado'
            ], 403);
        }
    
        Log::info("Validación de rol y propiedad del nodo aprobada");

        $request->validate([
            'name' => 'string|max:255',
            'about' => 'string|nullable',
            'profile_picture' => 'string|nullable',
            'social_media' => 'array|nullable',
            'coordinates' => 'string|nullable',
            'alt_places' => 'string|nullable',
            'ip_address' => 'string|nullable',
            'memorandum' => 'string|nullable',
        ]);
        Log::info("Validación de datos completada. Datos recibidos:", $request->all());

        $node->update($request->only([
            'name', 'about', 'profile_picture', 'social_media',
            'coordinates', 'alt_places', 'ip_address', 'memorandum'
        ]));

        Log::info("Nodo actualizado correctamente", ['node_id' => $node->id]);

        return response()->json([
            'status' => 200,
            'message' => 'Nodo actualizado correctamente',
            'data' => $node
        ]);
    }    
    
     /** 🔴 Admin elimina nodo (soft delete) */
     public function destroy(Request $request, $id)
     {
         if ($request->user()->role !== 'admin') {
             return response()->json(['status' => 403, 'error' => 'Solo los admins pueden eliminar nodos'], 403);
         }
 
         $node = Node::find($id);
         if (!$node) {
             return response()->json(['status' => 404, 'error' => 'Nodo no encontrado'], 404);
         }
 
         $node->update(['status' => 'inactivo']);
 
         return response()->json([
             'status' => 200,
             'message' => 'Nodo desactivado correctamente'
         ]);
     }
 
     /** 🟠 Admin reasigna el líder de un nodo */
     public function reassignLeader(Request $request, $id)
     {
         if ($request->user()->role !== 'admin') {
             return response()->json(['status' => 403, 'error' => 'Solo los admins pueden reasignar líderes'], 403);
         }
 
         $request->validate([
             'new_leader_id' => 'required|exists:users,id'
         ]);
 
         $node = Node::findOrFail($id);
         $oldLeader = User::find($node->leader_id);
         $newLeader = User::find($request->new_leader_id);
 
         if (!$newLeader || $newLeader->role !== 'member') {
             return response()->json(['status' => 400, 'error' => 'El nuevo líder debe ser un miembro válido'], 400);
         }
 
         // Cambiar roles
         $oldLeader->update(['role' => 'member']);
         $newLeader->update(['role' => 'node_leader']);
         $node->update(['leader_id' => $newLeader->id]);
 
         return response()->json([
             'status' => 200,
             'message' => 'Líder del nodo reasignado correctamente',
             'node' => $node
         ]);
     }
}
