<?php

namespace App\Http\Controllers;

use App\Helpers\JWTHandler;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Node;
use App\Models\Member;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Exception;

class InvitationController extends Controller
{
    private function generateNodeCode($nodeType)
    {
        $prefixes = [
            'sociedad_civil' => 'A',
            'empresarial' => 'E',
            'cientifico' => 'C',
            'funcion_publica' => 'F',
            'individual' => 'I',
        ];

        if (!isset($prefixes[$nodeType])) {
            throw new \Exception("Tipo de nodo inválido.");
        }

        $prefix = $prefixes[$nodeType];
        $lastNode = Node::orderBy('id', 'desc')->first();
        $nextNumber = $lastNode ? intval(substr($lastNode->code, 1)) + 1 : 1;
        return $prefix . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
    }

    /** Enviar invitación para un Admin */
    public function inviteAdmin(Request $request)
    {
        Log::info("Usuario autenticado:", ['user' => $request->user()]);

        $user = $request->user(); // Asegurar que no sea null
        if (!$user || !isset($user->role)) {
            return response()->json([
                'status' => 401, 
                'error' => 'Usuario no autenticado correctamente'
            ], 401);
        }
        
        // Verificar si es admin
        if ($user->role !== 'admin') {
            return response()->json([
                'status' => 403, 
                'error' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:invitations'
        ]);

        // Crear datos de invitación y generar token
        $token = JWTHandler::createToken((object)[
            'name' => $request->name,
            'email' => $request->email,
            'role_type' => 'admin'
        ], null, true);

        // Guardar en la BD
        $invitation = Invitation::create([
            'name' => $request->name,
            'email' => $request->email,
            'role_type' => 'admin',
            'token' => $token,
            'status' => 'pendiente',
            'sent_date' => now(),
            'expiration_date' => now()->addDays(7)
        ]);

        // Enviar email
        $url = env('APP_FRONTEND_URL', 'http://localhost:8080') . "/validate-invitation?token=$token";
        $body = "Hola {$request->name},<br>Has sido invitado a ProPlayas como Administrador.<br>
                Por favor regístrate aquí: <a href='$url'>$url</a>";

        MailService::sendMail($request->email, 'Invitación a ProPlayas', $body);

        return response()->json([
            'status' => 201,
            'message' => 'Invitación enviada con éxito',
            'data' => $invitation
        ], 201);
    }


    /** Enviar invitación para un Node Leader */
    public function inviteNodeLeader(Request $request)
    {
        Log::info("Usuario autenticado:", ['user' => $request->user()]);

        $user = $request->user(); // Asegurar que no sea null
        if (!$user || !isset($user->role)) {
            return response()->json([
                'status' => 401, 
                'error' => 'Usuario no autenticado correctamente'
            ], 401);
        }
        
        if ($user->role !== 'admin') {
            return response()->json([
                'status' => 403, 
                'error' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:invitations',
            'node_type' => 'required|string|max:255'
        ]);

         // Crear objeto con los datos de la invitación
        $invitationData = (object)[
            'name' => $request->name,
            'email' => $request->email,
            'role_type' => 'node_leader',
            'node_type' => $request->node_type
        ];

        // Generar token con los datos del usuario
        $token = JWTHandler::createToken($invitationData, null, true);

        // GUardar invitación en la BD
        $invitation = Invitation::create([
            'name' => $request->name,
            'email' => $request->email,
            'role_type' => 'node_leader',
            'node_type' => $request->node_type,
            'token' => $token,
            'status' => 'pendiente',
            'sent_date' => now(),
            'expiration_date' => now()->addDays(7)
        ]);

        #$url = env('APP_URL') . "/registro?token=$token";
        $url = env('APP_FRONTEND_URL', 'http://localhost:8080') . "/validate-invitation?token=$token";
        $body = "Hola {$request->name},
                <br>ProPlayas te ha enviado una invitación para crear un Nodo y registrarte como su Líder.<br>
                 Por favor regístrate aquí: <a href='$url'>$url</a>";

        MailService::sendMail($request->email, 'Invitación a ProPlayas', $body);

        return response()->json([
            'status' => 201,
            'message' => 'Invitación enviada con éxito',
            'data' => $invitation
        ], 201);
    }


    /** Enviar invitación para un Miembro */
    public function inviteMember(Request $request)
    {
        Log::info("Usuario autenticado:", ['user' => $request->user()]);

        $user = $request->user();
        if (!$user || !isset($user->role)) {
            return response()->json([
                'status' => 401, 
                'error' => 'Usuario no autenticado correctamente'
            ], 401);
        }
        
        if ($user->role !== 'node_leader') {
            return response()->json([
                'status' => 403, 
                'error' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:invitations'
        ]);

        // Obtener `node_id` del usuario autenticado desde la BD
        $node = Node::where('leader_id', $user->id)->first();
        $node_id = $node ? $node->id : null;

        Log::info("Node ID obtenido:", ['node_id' => $node_id]);

        if (!$node_id) {
            return response()->json([
                'status' => 400, 
                'error' => 'No se encontró el Node ID'
            ], 400);
        }

        // Crear objeto con los datos de la invitación
        $invitationData = (object)[
            'name' => $request->name,
            'email' => $request->email,
            'role_type' => 'member',
            'node_id' => $node_id
        ];

        // Generar token con la info
        $token = JWTHandler::createToken($invitationData, null, true);

        // Guardar invitación en DB
        $invitation = Invitation::create([
            'name' => $request->name,
            'email' => $request->email,
            'role_type' => 'member',
            'node_id' => $node_id,
            'token' => $token,
            'status' => 'pendiente',
            'sent_date' => now(),
            'expiration_date' => now()->addDays(7)
        ]);

        // Enviar email de invitación al usuario
        $url = env('APP_FRONTEND_URL', 'http://localhost:8080') . "/validate-invitation?token=$token";
        $body = "Hola {$request->name},<br>Has sido invitado a ProPlayas como Miembro de Nodo.<br>
                 Por favor regístrate aquí: <a href='$url'>$url</a>";

        MailService::sendMail($request->email, 'Invitación a ProPlayas', $body);

        return response()->json([
            'status' => 201,
            'message' => 'Invitación enviada con éxito',
            'data' => $invitation
        ], 201);
    }


    /** Validar invitación */
    public function validateInvitation($token)
    {
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation || $invitation->status !== 'pendiente' || now()->gt($invitation->expiration_date)) {
            return response()->json([
                'status' => 400, 
                'error' => 'Token inválido o expirado'
            ], 400);
        }

        return response()->json([
            'status' => 200, 
            'message' => 'Token válido', 
            'data' => [
                'name' => $invitation->name,
                'email' => $invitation->email,
                'role_type' => $invitation->role_type,
                'node_type' => $invitation->node_type ?? null,
                'node_id' => $invitation->node_id ?? null
            ]], 200);
    }

    /** Aceptar una invitación y registrar usuario y nodo */
    public function acceptInvitation(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8',
            'about' => 'string|nullable',
            'degree' => 'string|nullable',
            'postgraduate' => 'string|nullable',
            'expertise_area' => 'string|nullable',
            'research_work' => 'string|nullable',
            'profile_picture' => 'string|nullable',
            'social_media' => 'json|nullable',
            'node_name' => 'string|nullable',
            'about' => 'string|nullable',
            'country' => 'string|nullable',
            'city' => 'string|nullable'
        ]);

        // Decodificar el token
        try {
            $decoded = JWTHandler::decodeToken($request->token);
            Log::info("Token decodificado correctamente", (array) $decoded);
        } catch (Exception $e) {
            Log::error("Error al decodificar el token: " . $e->getMessage());
            return response()->json([
                'status' => 400,
                'error' => 'Token inválido o expirado'
            ], 400);
        }

        // Buscar la invitación asociada
        $invitation = Invitation::where('email', $decoded->email)
                                ->where('status', 'pendiente')
                                ->first();

        // Verificar si la invitación ya ha sido aceptada
        if (!$invitation) {
            Log::error("Invitación no encontrada o ya utilizada para: " . $decoded->email);
            return response()->json([
                'status' => 400,
                'error' => 'Invitación no encontrada, expirada o ya utilizada.'
            ], 400);
        }
        
        // Verificar si el usuario ya existe
        if (User::where('email', $decoded->email)->exists()) {
            Log::warning("Intento de registro con correo ya existente: " . $decoded->email);
            return response()->json(
                ['status' => 400, 
                'error' => 'El correo ya está registrado'
            ], 400);
        }

        // Decodificar la contraseña base64
        $decodedPassword = base64_decode($request->password);

        // Determinar el rol
        $role = $decoded->role_type;

        // Crear el usuario 
        $user = User::create([
            'name' => $decoded->name,
            'email' => $decoded->email,
            'password' => Hash::make($decodedPassword),
            'role' => $role,
            'about' => $request->about ?? null,
            'degree' => $request->degree ?? null,
            'postgraduate' => $request->postgraduate ?? null,
            'expertise_area' => $request->expertise_area ?? null,
            'research_work' => $request->research_work ?? null,
            'profile_picture' => $request->profile_picture ?? null,
            'social_media' => $request->social_media ? json_decode($request->social_media, true) : null,
            'status' => 'activo'
        ]);

        Log::info("Usuario creado correctamente con ID: " . $user->id);

        $node = null;

        if ($role === 'node_leader') {
            try {
                $nodeCode = $this->generateNodeCode($decoded->node_type);
            } catch (\Exception $e) {
                return response()->json(['status' => 400, 'error' => 'Tipo de nodo inválido.'], 400);
            }
            
            // Crear el Nodo
            $node = Node::create([
                'leader_id' => $user->id,
                'code' => $nodeCode,
                'type' => $decoded->node_type,
                'name' => $request->node_name,
                'about' => $request->about,
                'country' => $request->country,
                'city' => $request->city,
                'joined_in' => now()->year,
                'status' => 'activo'
            ]);
    
            Log::info("Nodo registrado correctamente con ID: " . $node->id);
        } elseif ($role === 'member') {
            // Asignar el usuario como miembro de un nodo
            $node_id = $decoded->node_id;

            if (!$node_id) {
                return response()->json([
                    'status' => 400,
                    'error' => 'No se pudo determinar el nodo'
                ], 400);
            }

            // Generar codigo de miembro
            $lastMember = Member::where('node_id', $node_id)->orderBy('id', 'desc')->first();
            $memberCode = $lastMember ? intval(substr($lastMember->member_code, -2)) + 1 : 1;
            $formattedCode = str_pad($memberCode, 2, '0', STR_PAD_LEFT);

            Member::create([
                'user_id' => $user->id,
                'node_id' => $node_id,
                'member_code' => strtoupper(Node::find($node_id)->code) . "." . $formattedCode,
                'status' => 'activo'
            ]);

            Log::info("Miembro registrado en nodo ID: " . $node_id);
        }

        // Actualizar la invitación como aceptada
        $invitation->update([
            'status' => 'aceptada', 
            'accepted_date' => now()
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Registro exitoso.',
            'data' => [
                'user' => $user, 
                'node' => $node
            ]
        ], 201);
    }
}
