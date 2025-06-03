<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Node;
use App\Models\Member;
use App\Helpers\ApiResponse;
use App\Helpers\JWTHandler;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Exception;

class NodeController extends Controller
{
    /** 🟢 Ver todos los nodos (público o autenticado) */
    public function index(Request $request)
    {
        $auth = null;

        // Detectar token manualmente (ya que la ruta es pública)
        $token = $request->bearerToken();

        if ($token) {
            try {
                $decoded = JWTHandler::decodeToken($token);
                $auth = (object)[
                    'id' => $decoded->sub ?? null,
                    'role' => $decoded->role ?? null
                ];
            } catch (\Exception $e) {
                \Log::warning('Token inválido al intentar acceder a /nodes', ['message' => $e->getMessage()]);
            }
        }

        $query = Node::select('id', 'code', 'type', 'name', 'city', 'country', 'members_count', 'joined_in', 'status');

        // Si no hay auth o no es admin => solo mostrar nodos activos
        if (!$auth || $auth->role !== 'admin') {
            $query->where('status', 'activo');
        }

        // Filtro de búsqueda
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                ->orWhere('code', 'like', '%' . $search . '%')
                ->orWhere('country', 'like', '%' . $search . '%')
                ->orWhere('city', 'like', '%' . $search . '%');
            });
        }

        $perPage = 200;
        $nodes = $query->orderBy('id')->paginate($perPage)->appends($request->query());

        return ApiResponse::success('Lista de nodos obtenida', $nodes->items(), [
            'current_page' => $nodes->currentPage(),
            'per_page' => $nodes->perPage(),
            'total' => $nodes->total(),
            'last_page' => $nodes->lastPage(),
        ]);
    }

    
    /** 🔵 Ver perfil de un nodo por ID o código (público) */
    public function show($identifier)
    {
        $node = is_numeric($identifier)
            ? Node::with(['leader:id,name,email,degree,postgraduate'])->find($id)
            : Node::with(['leader:id,name,email,degree,postgraduate'])->where('code', $identifier)->first();

        if (!$node) {
            return ApiResponse::notFound('Nodo no encontrado', 404);
        }

        return ApiResponse::success('Detalle del nodo obtenido correctamente', $node);
    }


    /** 🟠 Node leader edita su nodo */
    public function update(Request $request, $id)
    {
        Log::info("Intentando actualizar nodo ID: $id");
        $node = Node::find($id);

        if (!$node) {
            Log::warning("Nodo no encontrado con ID: $id");
            return ApiResponse::notFound('Nodo no encontrado', 404);
        }

        $user = $request->user();
        Log::info("Usuario autenticado:", (array) $user);

        if (!$user || !isset($user->id) || !isset($user->role)) {
            Log::error("No se pudo detectar el rol del usuario");
            return ApiResponse::unauthenticated('Usuario no autenticado correctamente', 401);
        }
    
        if ($user->role !== 'node_leader') {
            Log::warning("Usuario con ID {$user->id} no es node_leader, es {$user->role}");
            return ApiResponse::unauthorized('Unauthorized', 403);
        }
        
        if ($user->id != $node->leader_id) {
            Log::warning("Usuario con ID {$user->id} no es el líder del nodo ID $id");
            return ApiResponse::unauthorized('Unauthorized', 403);
        }
    
        Log::info("Validación de rol y propiedad del nodo aprobada");

        $request->validate([
            'name' => 'string|max:255',
            'about' => 'string|nullable',
            'country' => 'string|nullable|max:255',
            'city' => 'string|nullable|max:255',
            'ip_address' => 'string|nullable',
            'coordinates' => 'string|nullable',
            'alt_places' => 'string|nullable',
            'joined_in' => 'nullable|integer|min:2000|max:' . now()->year,
            'social_media' => 'array|nullable',
        ]);
        Log::info("Validación de datos completada. Datos recibidos:", $request->all());

        $node->update($request->only([
            'name', 'about', 'country', 'city', 
            'ip_address', 'coordinates', 'alt_places', 
            'joined_in', 'social_media'
        ]));

        Log::info("Nodo actualizado correctamente", ['node_id' => $node->id]);

        return ApiResponse::success('Nodo actualizado correctamente', $node->only([
            'id', 'code', 'type', 'name', 'city', 'country', 'status', 'joined_in', 'profile_picture'
        ]));
    }

    /** 🟡 Editar imagen de perfil de un nodo */
    public function uploadProfilePicture(Request $request)
    {
        try {
            \Log::info('Intentando subir imagen de perfil del nodo.');

            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,webp|max:5120',
            ]);

            $user = $request->user();
            $node = $user->node; // Asumimos que existe esta relación

            if (!$node) {
                \Log::warning('El usuario no tiene nodo asignado.');
                return ApiResponse::notFound('Este usuario no está asignado a ningún nodo', 404);
            }

            $oldFilename = $node->profile_picture; // solo el nombre del archivo
            $newFilename = FileUploadService::uploadImage($request->file('image'), 'profiles', $oldFilename);

            $node->profile_picture = $newFilename;
            $node->save();

            \Log::info('Imagen del nodo actualizada correctamente.', ['filename' => $newFilename]);

            return ApiResponse::success('Imagen del nodo actualizada correctamente', $node->only([
                'id', 'name', 'leader_id', 'profile_picture'
            ]));
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Validación fallida al subir imagen de nodo.', ['errors' => $e->errors()]);
            return ApiResponse::error('Error de validación', 422, ['errors' => $e->errors()]);
        } catch (\Throwable $e) {
            \Log::error('Error inesperado al subir imagen del nodo:', ['exception' => $e]);
            return ApiResponse::error('Error inesperado al subir imagen del nodo', 500, [
                'debug' => $e->getMessage()
            ]);
        }
    }


    /** 🟡 Editar memorandum de nodo */
    public function uploadMemorandum(Request $request)
    {
        try {
            \Log::info('Intentando subir memorandum del nodo.');

            $request->validate([
                'file' => 'required|file|mimes:pdf,docx|max:10240'
            ]);

            $user = $request->user();
            $node = $user->node; // Asumimos que existe esta relación

            if (!$node) {
                \Log::warning('El usuario no tiene nodo asignado.');
                return ApiResponse::notFound('Este usuario no está asignado a ningún nodo', 404);
            }

            $oldFilename = $node->memorandum; // solo el nombre del archivo
            $newFilename = FileUploadService::uploadFile($request->file('file'), 'docs', $oldFilename);

            $node->memorandum = $newFilename;
            $node->save();

            \Log::info('Imagen del nodo actualizada correctamente.', ['filename' => $newFilename]);

            return ApiResponse::success('Memorandum del nodo actualizada correctamente', $node->only([
                'id', 'name', 'leader_id', 'code', 'memorandum'
            ]));
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Validación fallida al subir memorandum del nodo.', ['errors' => $e->errors()]);
            return ApiResponse::error('Error de validación', 422, ['errors' => $e->errors()]);
        } catch (\Throwable $e) {
            \Log::error('Error inesperado al subir memorandum del nodo:', ['exception' => $e]);
            return ApiResponse::error('Error inesperado al subir memorandum del nodo', 500, [
                'debug' => $e->getMessage()
            ]);
        }
    }
    
    /** 🟠 Admin activa/desactiva nodo */
    public function toggleStatus(Request $request, $id)
    {
        $user = $request->user();
        if ($user->role !== 'admin') {
            return ApiResponse::unauthorized('Solo los administradores pueden activar o desactivar nodos');
        }

        $node = Node::find($id);
        if (!$node) {
            return ApiResponse::notFound('Nodo no encontrado', 404);
        }

        $node->status = $node->status === 'activo' ? 'inactivo' : 'activo';
        $node->save();

        return ApiResponse::success('Estado del nodo actualizado correctamente', $node->only([
            'id', 'code', 'name', 'status'
        ]));
    }

    
     /** 🔴 Admin elimina nodo (soft delete) */
     public function destroy(Request $request, $id)
     {
         if ($request->user()->role !== 'admin') {
            return ApiResponse::unauthorized('Unauthorized: Solo los admins pueden eliminar nodos', 403);
         }
 
         $node = Node::find($id);
         if (!$node) {
            return ApiResponse::notFound('Nodo no encontrado', 404);
         }
 
         $node->delete();

         return ApiResponse::success('Nodo eliminado correctamente', $node);
     }
 
     /** 🟠 Admin reasigna el líder de un nodo */
     public function reassignLeader(Request $request, $id)
     {
         if ($request->user()->role !== 'admin') {
            return ApiResponse::unauthorized('Unauthorized: Solo los admins pueden reasignar líderes', 403);
         }
 
         $request->validate([
             'new_leader_id' => 'required|exists:users,id'
         ]);
 
         $node = Node::findOrFail($id);
         $oldLeader = User::find($node->leader_id);
         $newLeader = User::find($request->new_leader_id);
 
         if (!$newLeader || $newLeader->role !== 'member') {
            return ApiResponse::error('El nuevo líder debe ser un miembro válido', 400);
         }
 
         // Cambiar roles
         $oldLeader->update(['role' => 'member']);
         $newLeader->update(['role' => 'node_leader']);
         $node->update(['leader_id' => $newLeader->id]);
 
         return ApiResponse::success('Líder del nodo reasignado correctamente', $node);
     }
}
