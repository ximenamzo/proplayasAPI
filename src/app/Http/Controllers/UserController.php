<?php

namespace App\Http\Controllers;

use App\Helpers\JWTHandler;
use App\Models\User;
use App\Models\Node;
use App\Models\Member;
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
            return response()->json([
                'status' => 403, 
                'error' => 'Este endpoint solo está disponible en entorno de desarrollo'
            ], 403);
        }

        $users = User::all();

        return response()->json([
            'status' => 200,
            'message' => 'Lista de usuarios obtenida',
            'data' => $users
        ]);
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
            'profile_picture', 'social_media', 'status')
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 404,
                'error' => 'Usuario no encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'data' => $user
        ]);
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
            return response()->json([
                'status' => 404, 
                'error' => 'Nodo no encontrado'
            ], 404);
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
          ->get();

        $response = [];

        // Agregar líder del nodo como el primer "miembro"
        $leaderUser = User::select('id', 'name', 'username', 'email', 'expertise_area', 'research_work', 'status')
                          ->find($node->leader_id);

        if ($leaderUser && ($leaderUser->status === 'activo' || !$onlyActive)) {
            $response[] = [
                'id' => null,
                'user_id' => $leaderUser->id,
                'node_id' => $node->id,
                'member_code' => strtoupper($node->code) . '.00',
                'name' => $leaderUser->name,
                'email' => $leaderUser->email,
                'username' => $leaderUser->username,
                'research_line' => $leaderUser->expertise_area,
                'work_area' => $leaderUser->research_work,
                'status' => $leaderUser->status,
            ];
        }

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
                'status' => $m->user->status,
            ];
        }

        return response()->json([
            'status' => 200,
            'data' => $response
        ]);
    }


    /** 🔵 ADMIN: Listar todos los miembros del sistema agrupados por nodo */
    public function listAllMembers(Request $request)
    {
        $auth = $request->user();

        if (!$auth || $auth->role !== 'admin') {
            return response()->json([
                'status' => 403,
                'error' => 'Solo los administradores pueden acceder a esta lista'
            ], 403);
        }

        $nodes = Node::with([
            'leader' => function ($q) {
                $q->select(
                    'id', 'name', 'email', 'role', 'status'
                );
            },
            'members.user' => function ($q) {
                $q->select(
                    'id', 'name', 'email', 'role', 'status'
                );
            }
        ])
        ->orderBy('id')
        ->get();

        $output = [];

        foreach ($nodes as $node) {
            // Incluir al líder del nodo
            if ($node->leader) {
                $output[] = [
                    'name' => $node->leader->name,
                    'email' => $node->leader->email,
                    'role' => 'node_leader',
                    'node_id' => $node->id,
                    'node_code' => strtoupper($node->code),
                    'status' => $node->leader->status,
                ];
            }

            // Incluir a los miembros
            foreach ($node->members as $member) {
                if ($member->user) {
                    $output[] = [
                        'name' => $member->user->name,
                        'email' => $member->user->email,
                        'role' => 'member',
                        'node_id' => $node->id,
                        'node_code' => strtoupper($node->code),
                        'status' => $member->user->status,
                    ];
                }
            }
        }

        // Ordenar: líderes primero, luego miembros; activos arriba, inactivos abajo
        $output = collect($output)->sortBy([
            ['node_id', 'asc'],
            ['role', 'desc'], // node_leader < member
            ['status', 'asc'], // activo primero
        ])->values();

        return response()->json([
            'status' => 200,
            'message' => 'Miembros de ProPlayas listados correctamente',
            'data' => $output
        ]);
    }


    /** 🟠 Editar perfil propio (solo miembros y node_leaders) */
    public function update(Request $request, $id)
    {
        Log::info("Intentando actualizar usuario ID: $id");
        $user = User::find($id);

        if (!$user || !in_array($user->role, ['node_leader', 'member'])) {
            return response()->json([
                'status' => 404, 
                'error' => 'Usuario no encontrado'
            ], 404);
        }

        $authId = $request->user()?->sub ?? $request->user()?->id;

        if ($authId !== $user->id) {
            return response()->json([
                'status' => 403, 
                'error' => 'Usuario no autorizado'
            ], 403);
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
            'social_media' => 'array|nullable'
        ]);

        $user->update($request->only($fields));

        return response()->json([
            'status' => 200,
            'message' => 'Perfil actualizado correctamente',
            'data' => $user->only([
                'id', 'name', 'username', 'email', 'role', 'about', 
                'degree', 'postgraduate', 'expertise_area', 'research_work', 
                'profile_picture', 'social_media', 'status'
            ])
        ]);
    }

    
    /** 🔴 Soft delete: admin elimina a node_leader o node_leader elimina miembro */
    public function destroy($id, Request $request)
    {
        $target = User::find($id);

        if (!$target || !in_array($target->role, ['node_leader', 'member'])) {
            return response()->json([
                'status' => 404, 
                'error' => 'Usuario no encontrado'
            ], 404);
        }

        $auth = $request->user();

        if (
            ($target->role === 'node_leader' && $auth->role !== 'admin') ||
            ($target->role === 'member' && !in_array($auth->role, ['admin', 'node_leader']))
        ) {
            return response()->json([
                'status' => 403, 
                'error' => 'No autorizado'
            ], 403);
        }

        $target->status = 'inactivo';
        $target->save();

        return response()->json([
            'status' => 200,
            'message' => 'Usuario desactivado correctamente'
        ]);
    }
}
