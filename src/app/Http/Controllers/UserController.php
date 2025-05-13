<?php

namespace App\Http\Controllers;

use App\Helpers\JWTHandler;
use App\Helpers\ApiResponse;
use App\Models\User;
use App\Models\Node;
use App\Models\Member;
use App\Services\FileUploadService;
use Firebase\JWT\ExpiredException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /** 🟢 Obtener todos los usuarios */
    // Endpoint solo disponible en entorno de desarrollo
    public function index()
    {
        if (app()->environment() !== 'local') {
            return ApiResponse::unauthorized('Unauthorized: Este endpoint solo está disponible en entorno de desarrollo', 403);
        }

        $users = User::all();

        return ApiResponse::success('Lista de usuarios obtenida', $users);
    }


    /** 🔵 Obtener perfil del usuario autenticado */
    public function profile(Request $request)
    {
        Log::info("Request del profile: $request");

        $user = $request->user();
    
        if (!$user) {
            return ApiResponse::unauthenticated('Token inválido o usuario no autenticado', 401);
        }
    
        $userModel = User::where('id', $user->sub ?? $user->id)->first();
    
        if (!$userModel) {
            return ApiResponse::notFound('Usuario no encontrado', 404);
        }
    
        return ApiResponse::success('Perfil del usuario autenticado',
            $userModel->only([
                'id', 'name', 'username', 'email', 'role', 'about',
                'degree', 'postgraduate', 'expertise_area', 'research_work',
                'profile_picture', 'country', 'city', 'social_media', 'status'
            ])
        );
    }


    /** 🔵 Obtener un usuario por ID */
    public function show($id, Request $request)
    {
        $authUser = $request->user();

        $query = is_numeric($id)
            ? User::where('id', $id)
            : User::where('username', $id);
        
        $query->whereIn('role', ['node_leader', 'member']);

        // Solo admin o node leader puede ver usuarios inactivos
        if (!in_array($authUser?->role, ['admin', 'node_leader'])) {
            $query->where('status', 'activo');
        }

        $user = $query->select(
            'id', 'name', 'username', 'email', 'role', 'about',
            'degree', 'postgraduate', 'expertise_area', 'research_work',
            'profile_picture', 'country', 'city', 'social_media', 'status')
            ->first();

        if (!$user) {
            return ApiResponse::notFound('Usuario no encontrado', 404);
        }

        return ApiResponse::success('Detalle del usuario obtenido correctamente', $user);
    }


    /** 🟢 Listar miembros de UN NODO (por ID o por código) */
    public function listByNode($identifier, Request $request)
    {
        $authUser = null;
        $token = $request->bearerToken();

        if ($token) {
            try {
                $decoded = JWTHandler::decodeToken($token);
                $authUser = json_decode(json_encode($decoded));
            } catch (\Firebase\JWT\ExpiredException $e) {
                \Log::warning("Token expirado al listar miembros de nodo");
            } catch (\Exception $e) {
                \Log::warning("Error al decodificar token: " . $e->getMessage());
            }
        }

        $node = is_numeric($identifier)
            ? Node::find($identifier)
            : Node::where('code', $identifier)->first();

        if (!$node) {
            return ApiResponse::notFound('Nodo no encontrado', 404);
        }

        // Filtro según permisos
        $onlyActive = !in_array($authUser?->role, ['admin', 'node_leader']);

        $members = Member::with(['user' => function ($q) use ($onlyActive) {
            $q->select('id', 'name', 'username', 'email', 'expertise_area', 'research_work', 'status');
            if ($onlyActive) {
                $q->where('status', 'activo');
            }
        }])->where('node_id', $node->id)
          ->orderByRaw("CASE WHEN status = 'activo' THEN 0 ELSE 1 END")
          ->orderBy('member_code')
          ->get();

        $response = [];

        // Agregar miembros reales
        foreach ($members as $m) {
            if (!$m->user) continue;

            $response[] = [
                'id' => $m->id,
                'user_id' => $m->user->id,
                'node_id' => $m->node_id,
                'member_code' => $m->member_code,
                'name' => $m->user->name,
                'email' => $m->user->email,
                'username' => $m->user->username,
                'research_line' => $m->user->expertise_area,
                'work_area' => $m->user->research_work,
                'status' => $m->status,
            ];
        }

        return ApiResponse::success('Lista de miembros obtenida', $response);
    }


    /** 🔵 ADMIN: Listar todos los miembros del sistema agrupados por nodo */
    public function listAllMembers(Request $request)
    {
        $auth = $request->user();
    
        if (!$auth || $auth->role !== 'admin') {
            return ApiResponse::unauthorized('Solo los administradores pueden acceder a esta lista');
        }
    
        $nodes = Node::with([
            'leader:id,name,email,role,status',
            'members.user:id,name,email,role,status'
        ])->orderBy('id')->get();
    
        $results = collect();
    
        foreach ($nodes as $node) {
            $leaderId = $node->leader?->id;
    
            if ($node->leader) {
                $results->push([
                    'name' => $node->leader->name,
                    'email' => $node->leader->email,
                    'role' => 'node_leader',
                    'node_id' => $node->id,
                    'node_code' => strtoupper($node->code),
                    'status' => $node->leader->status,
                ]);
            }
    
            foreach ($node->members as $member) {
                if ($member->user && $member->user->id !== $leaderId) {
                    $results->push([
                        'name' => $member->user->name,
                        'email' => $member->user->email,
                        'role' => 'member',
                        'node_id' => $node->id,
                        'node_code' => strtoupper($node->code),
                        'status' => $member->user->status,
                    ]);
                }
            }
        }
    
        // Filtrar por búsqueda
        if ($search = $request->input('search')) {
            $results = $results->filter(fn($item) =>
                str_contains(strtolower($item['name']), strtolower($search)) ||
                str_contains(strtolower($item['email']), strtolower($search))
            );
        }
    
        // Filtrar por rol
        if ($role = $request->input('role')) {
            $results = $results->filter(fn($item) => $item['role'] === $role);
        }
    
        // Ordenar: líder > miembro, activo > inactivo
        $results = $results->sortBy([
            ['node_id', 'asc'],
            ['role', 'desc'],   // node_leader antes que member
            ['status', 'asc'],  // activo antes que inactivo
        ])->values();
    
        // Paginación
        $perPage = 20;
        $currentPage = (int) $request->input('page', 1);
        $paged = $results->forPage($currentPage, $perPage)->values();
    
        return response()->json([
            'status' => 200,
            'message' => 'Lista de usuarios de ProPlayas obtenida correctamente',
            'data' => $paged,
            'meta' => [
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total' => $results->count(),
                'last_page' => ceil($results->count() / $perPage),
            ]
        ]);
    }
      

    /** 🟠 Editar perfil propio usando solo el token */
    public function updateProfile(Request $request)
    {
        $authUser = $request->user();

        if (!$authUser) {
            return ApiResponse::unauthenticated('Token inválido o usuario no autenticado', 401);
        }

        $fields = [
            'name', 'about', 
            'degree', 'postgraduate',
            'expertise_area', 'research_work', 
            'profile_picture', 'social_media'
        ];

        $request->validate([
            'name' => 'string|nullable|max:255',
            'about' => 'string|nullable',
            'degree' => 'string|nullable|max:255',
            'postgraduate' => 'string|nullable|max:255',
            'expertise_area' => 'string|nullable|max:255',
            'research_work' => 'string|nullable|max:255',
            'profile_picture' => 'string|nullable|max:255',
            'country' => 'string|nullable|max:255',
            'city' => 'string|nullable|max:255',
            'social_media' => 'array|nullable'
        ]);
            
        
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $authUser->$field = $request->$field;
            }
        }
    
        $authUser->save();
        
        Log::info('Datos actualizados de usuario:', $authUser->toArray());
        Log::info('Request all:', $request->all());
        Log::info('Has file:', ['profile_picture_file' => $request->hasFile('profile_picture_file')]);
        Log::debug('FILES:', $request->allFiles());
        Log::debug('IS FILE?', ['has_file' => $request->hasFile('profile_picture_file')]);
        Log::debug('USER FINAL:', $authUser->toArray());


        
        //$authUser->update($request->only($fields));

        return ApiResponse::success('Perfil actualizado correctamente', $authUser->only([
                'id', 'name', 'username', 'email', 'role', 'about', 
                'degree', 'postgraduate', 'expertise_area', 'research_work', 
                'profile_picture', 'country', 'city', 'social_media', 'status'
            ])
        );
    }


    /** 🟡 Subir imagen de perfil de usuario */
    public function uploadProfilePicture(Request $request)
    {
        try {
            \Log::info('Intentando subir imagen de perfil del usuario.');
    
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,webp|max:5120'
            ]);
    
            $user = $request->user();
            \Log::info('Usuario autenticado:', ['id' => $user->id]);
    
            $oldFilename = $user->profile_picture; // ahora es solo el nombre del archivo
            $newFilename = FileUploadService::uploadImage($request->file('image'), 'profiles', $oldFilename);
    
            $user->profile_picture = $newFilename; // solo guardamos el nombre
            $user->save();
    
            \Log::info('Imagen de perfil actualizada correctamente.', ['filename' => $newFilename]);
    
            return ApiResponse::success('Imagen de perfil actualizada correctamente', [
                // Construimos la URL como lo hará el frontend
                'url' => asset("storage/uploads/profiles/{$newFilename}"),
                'user' => $user->only(['id', 'name', 'profile_picture'])
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Validación fallida al subir imagen de perfil.', ['errors' => $e->errors()]);
            return ApiResponse::error('Error de validación', 422, ['errors' => $e->errors()]);
        } catch (\Throwable $e) {
            \Log::error('Error inesperado al subir imagen de perfil:', ['exception' => $e]);
            return ApiResponse::error('Error inesperado al subir imagen de perfil', 500, [
                'debug' => $e->getMessage()
            ]);
        }
    }


    /** 🟠 Editar perfil propio pasando id y token (para postman) */
    public function update(Request $request, $id)
    {
        Log::info("Intentando actualizar usuario ID: $id");
        $user = User::find($id);

        if (!$user || !in_array($user->role, ['admin', 'node_leader', 'member'])) {
            return ApiResponse::notFound('Usuario no encontrado', 404);
        }

        $authId = $request->user()?->sub ?? $request->user()?->id;

        if ($authId !== $user->id) {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }

        $fields = [
            'name', 'about', 
            'degree', 'postgraduate',
            'expertise_area', 'research_work', 
            'profile_picture', 'social_media',
            'country', 'city'
        ];

        $request->validate([
            'name' => 'string|nullable|max:255',
            'about' => 'string|nullable',
            'degree' => 'string|nullable|max:255',
            'postgraduate' => 'string|nullable|max:255',
            'expertise_area' => 'string|nullable|max:255',
            'research_work' => 'string|nullable|max:255',
            'profile_picture' => 'string|nullable|max:255',
            'country' => 'string|nullable|max:255',
            'city' => 'string|nullable|max:255',
            'social_media' => 'array|nullable'
        ]);

        $user->update($request->only($fields));

        return ApiResponse::success('Perfil actualizado correctamente', $user->only([
                'id', 'name', 'username', 'email', 'role', 'about', 
                'degree', 'postgraduate', 'expertise_area', 'research_work', 
                'profile_picture', 'country', 'city', 'social_media', 'status'
            ])
        );
    }


    /** 🟠 Activar o desactivar un usuario */
    public function toggleStatus($id, Request $request)
    {
        $user = User::find($id);

        if (!$user || !in_array($user->role, ['node_leader', 'member'])) {
            return ApiResponse::notFound('Usuario no encontrado', 404);
        }

        $auth = $request->user();

        // Validar permiso
        if (
            ($user->role === 'node_leader' && $auth->role !== 'admin') ||
            ($user->role === 'member' && !in_array($auth->role, ['admin', 'node_leader']))
        ) {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }

        // Alternar el estado del usuario
        $user->status = $user->status === 'activo' ? 'inactivo' : 'activo';
        $user->save();

        return ApiResponse::success('Usuario activado/desactivado correctamente', $user->only([
            'id', 'name', 'username', 'email', 'role', 'status'
        ]));
    }

    
    /** 🔴 Eliminar permanentemente un usuario */
    public function destroy($id, Request $request)
    {
        $target = User::find($id);

        if (!$target || !in_array($target->role, ['node_leader', 'member'])) {
            return ApiResponse::notFound('Usuario no encontrado', 404);
        }

        $auth = $request->user();

        if (
            ($target->role === 'node_leader' && $auth->role !== 'admin') ||
            ($target->role === 'member' && !in_array($auth->role, ['admin', 'node_leader']))
        ) {
            return ApiResponse::unauthorized('Unauthorized', 403);
        }

        $target->delete();

        return ApiResponse::success('Usuario eliminado permanentemente de forma correcta', $target);
    }
    
}
