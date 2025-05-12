<?php

namespace App\Http\Controllers;

use App\Helpers\JWTHandler;
use App\Helpers\ApiResponse;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Node;
use App\Models\Member;
use App\Services\FileUploadService;
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
            return ApiResponse::unauthenticated('Usuario no autenticado correctamente', 401);
        }
        
        // Verificar si es admin
        if ($user->role !== 'admin') {
            return ApiResponse::unauthorized('Unauthorized', 403);
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

        return ApiResponse::created('Invitación enviada con éxito', $invitation);
    }


    /** Enviar invitación para un Node Leader */
    public function inviteNodeLeader(Request $request)
    {
        Log::info("Usuario autenticado:", ['user' => $request->user()]);

        $user = $request->user(); // Asegurar que no sea null
        if (!$user || !isset($user->role)) {
            return ApiResponse::unauthenticated('Usuario no autenticado correctamente', 401);
        }
        
        if ($user->role !== 'admin') {
            return ApiResponse::unauthorized('Unauthorized', 403);
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

        return ApiResponse::created('Invitación enviada con éxito', $invitation);
    }


    /** Enviar invitación para un Miembro */
    public function inviteMember(Request $request)
    {
        Log::info("Usuario autenticado:", ['user' => $request->user()]);

        $user = $request->user();
        if (!$user || !isset($user->role)) {
            return ApiResponse::unauthenticated('Usuario no autenticado correctamente', 401);
        }
        
        if ($user->role !== 'node_leader') {
            return ApiResponse::unauthorized('Unauthorized', 403);
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
            return ApiResponse::error('No se encontró el Node ID', 400);
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

        return ApiResponse::created('Invitación enviada con éxito', $invitation);
    }


    /** Validar invitación */
    public function validateInvitation($token)
    {
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation || $invitation->status !== 'pendiente' || now()->gt($invitation->expiration_date)) {
            return ApiResponse::error('Token inválido o expirado', 400);
        }

        return ApiResponse::success('Token válido', [
            'name' => $invitation->name,
            'email' => $invitation->email,
            'role_type' => $invitation->role_type,
            'node_type' => $invitation->node_type ?? null,
            'node_id' => $invitation->node_id ?? null
        ]);
    }

    /** Aceptar una invitación y registrar usuario y nodo */
    public function acceptInvitation(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8',
            'username' => 'string|nullable|max:255',
            'about_user' => 'string|nullable',
            'degree' => 'string|nullable|max:255',
            'postgraduate' => 'string|nullable|max:255',
            'expertise_area' => 'string|nullable|max:255',
            'research_work' => 'string|nullable|max:255',
            'profile_picture_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'profile_picture' => 'nullable|url',
            'country_user' => 'string|nullable|max:255',
            'city_user' => 'string|nullable|max:255',
            'social_media' => 'array|nullable',

            // Nodo
            'node_name' => 'string|nullable|max:255',
            'profile_picture_node_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'profile_picture_node' => 'nullable|url',
            'about_node' => 'string|nullable',
            'country_node' => 'string|nullable|max:255',
            'city_node' => 'string|nullable|max:255',
            'ip_address' => 'string|nullable|max:255',
            'coordinates' => 'string|nullable|max:255',
            'alt_places' => 'string|nullable',
            'joined_in' => 'nullable|integer|min:2000|max:' . now()->year,
            'id' => 'string|nullable|max:255',
            'social_media_node' => 'array|nullable',
            'memorandum' => 'string|nullable|max:255',
        ]);

        // Decodificar el token
        try {
            $decoded = JWTHandler::decodeToken($request->token);
            Log::info("Token decodificado correctamente", (array) $decoded);
        } catch (Exception $e) {
            Log::error("Error al decodificar el token: " . $e->getMessage());
            return ApiResponse::error('Token inválido o expirado', 400);
        }

        // Buscar la invitación asociada
        $invitation = Invitation::where('email', $decoded->email)
                                ->where('status', 'pendiente')
                                ->first();

        // Verificar si la invitación ya ha sido aceptada
        if (!$invitation) {
            Log::error("Invitación no encontrada o ya utilizada para: " . $decoded->email);
            return ApiResponse::error('Invitación no encontrada, expirada o ya utilizada.', 400);
        }
        
        // Verificar si el usuario ya existe
        if (User::where('email', $decoded->email)->exists()) {
            Log::warning("Intento de registro con correo ya existente: " . $decoded->email);
            return ApiResponse::error('El correo ya está registrado', 400);
        }

        // Decodificar la contraseña base64
        $decodedPassword = base64_decode($request->password);

        // Determinar el rol
        $role = $decoded->role_type;

        // Si no se proporciona un username, usar el prefijo del correo
        $username = $request->username ?? explode('@', $decoded->email)[0];
        // Verificar si ya existe ese username y agregar sufijo incremental
        $originalUsername = $username;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $originalUsername . $counter;
            $counter++;
        }

        // Manejar imagen de perfil si se envía como archivo o como url
        $profilePicturePath = null;
        if ($request->hasFile('profile_picture_file')) {
            $profilePicturePath = FileUploadService::uploadImage($request->file('profile_picture_file'), 'profiles');
        } elseif ($request->filled('profile_picture')) {
            $profilePicturePath = $request->profile_picture;
        }

        // Crear el usuario 
        $user = User::create([
            'name' => $decoded->name,
            'username' => $username,
            'email' => $decoded->email,
            'password' => Hash::make($decodedPassword),
            'role' => $role,
            'about' => $request->about_user ?? null,
            'degree' => $request->degree ?? null,
            'postgraduate' => $request->postgraduate ?? null,
            'expertise_area' => $request->expertise_area ?? null,
            'research_work' => $request->research_work ?? null,
            'profile_picture' => $profilePicturePath ?? null,
            'country' => $request->country_user ?? null,
            'city' => $request->city_user ?? null,
            'social_media' => $request->social_media ? json_decode($request->social_media, true) : null,
            'status' => 'activo'
        ]);

        Log::info("Usuario creado correctamente con ID: " . $user->id);

        $node = null;

        if ($role === 'node_leader') {
            try {
                $nodeCode = $this->generateNodeCode($decoded->node_type);
            } catch (\Exception $e) {
                return ApiResponse::error('Tipo de nodo inválido.', 400);
            }

            $nodeProfilePicture = null;
            if ($request->hasFile('profile_picture_node_file')) {
                $nodeProfilePicture = FileUploadService::uploadImage($request->file('profile_picture_node_file'), 'nodes');
            } elseif ($request->filled('profile_picture_node')) {
                $nodeProfilePicture = $request->profile_picture_node;
            }
            
            // Crear el Nodo
            $node = Node::create([
                'leader_id' => $user->id,
                'code' => $nodeCode,
                'type' => $decoded->node_type,
                'name' => $request->node_name,
                'profile_picture' => $nodeProfilePicture ?? null,
                'about' => $request->about_node,
                'country' => $request->country_node,
                'city' => $request->city_node,
                'ip_address' => $request->ip_address ?? null,
                'coordinates' => $request->coordinates ?? null,
                'alt_places' => $request->alt_places ?? null,
                'joined_in' => $request->joined_in ?? now()->year,
                'members_count' => 1, // El líder es el primer miembro
                'id' => $request->id ?? null,
                'social_media' => $request->social_media_node ? json_decode(json_encode($request->social_media_node), true) : null,
                'memorandum' => $request->memorandum ?? null,
                'status' => 'activo'
            ]);
    
            Log::info("Nodo registrado correctamente con ID: " . $node->id);

            Member::create([
                'user_id' => $user->id,
                'node_id' => $node->id,
                'member_code' => strtoupper($node->code) . '.00', // por ser primer miembro
                'status' => 'activo'
            ]);
            
            Log::info("Líder registrado también como miembro con código: " . strtoupper($node->code) . '.00');
            
        } elseif ($role === 'member') {
            // Asignar el usuario como miembro de un nodo
            $node_id = $decoded->node_id;

            if (!$node_id) {
                return ApiResponse::error('No se pudo determinar el nodo', 400);
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

            // Incrementar el contador de miembros en el nodo
            Node::where('id', $node_id)->increment('members_count');

            Log::info("Miembro registrado en nodo ID: " . $node_id);
        }

        // Actualizar la invitación como aceptada
        $invitation->update([
            'status' => 'aceptada', 
            'accepted_date' => now()
        ]);

        return ApiResponse::created('Registro exitoso.', [
            'user' => $user->refresh(),
            'node' => $node
        ]);
    }
}
