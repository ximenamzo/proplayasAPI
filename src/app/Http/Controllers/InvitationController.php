<?php

namespace App\Http\Controllers;

use App\Helpers\JWTHandler;
use App\Models\Invitation;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    /** Enviar invitación para un Node Leader */
    public function inviteNodeLeader(Request $request)
    {
        if ($request->user->role !== 'admin') {
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
        $token = JWTHandler::createToken($invitationData);

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
    public function inviteMember(Request $request, $node_id)
    {
        if ($request->user->role !== 'node_leader') {
            return response()->json(['status' => 403, 'error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:invitations'
        ]);

        $token = Str::random(32);
        $invitation = Invitation::create([
            'name' => $request->name,
            'email' => $request->email,
            'role_type' => 'member',
            'node_type' => null,
            'node_id' => $node_id,
            'token' => $token,
            'status' => 'pendiente',
            'sent_date' => now(),
            'expiration_date' => now()->addDays(7)
        ]);

        $url = env('APP_URL') . "/register?token=$token";
        $body = "Hola {$request->name},<br>Has sido invitado a ProPlayas como Miembro.<br>
                 Por favor regístrate aquí: <a href='$url'>$url</a>";

        MailService::sendMail($request->email, 'Invitación a ProPlayas', $body);

        return response()->json(['status' => 200, 'message' => 'Invitación enviada']);
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
                'node_type' => $invitation->node_type,
            ]
        ], 200);
    }

    /** Aceptar una invitación y registrar usuario y nodo */
    public function acceptInvitation(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8',
            'node_name' => 'required|string',
            'about' => 'string|nullable',
            'country' => 'required|string',
            'city' => 'required|string',
            'degree' => 'string|nullable',
            'postgraduate' => 'string|nullable',
            'expertise_area' => 'string|nullable',
            'research_work' => 'string|nullable',
            'profile_picture' => 'string|nullable',
            'social_media' => 'json|nullable'
        ]);

        // Decodificar el token
        try {
            $decoded = JWTHandler::decodeToken($request->token);
        } catch (Exception $e) {
            return response()->json([
                'status' => 400,
                'error' => 'Token inválido o expirado'
            ], 400);
        }

        $invitation = Invitation::where('email', $decoded->email)
                                ->where('status', 'pendiente')
                                ->first();

        if (!$invitation || $invitation->status !== 'pendiente') {
            return response()->json([
                'status' => 400,
                'error' => 'Invitación no encontrada, expirada o ya utilizada.'
            ], 400);
        }

        // Decodificar la contraseña base64
        $decodedPassword = base64_decode($request->password);

        // Crear el usuario Node Leader
        $user = User::create([
            'name' => $decoded->name,
            'email' => $decoded->email,
            'password' => Hash::make($decodedPassword),
            'role' => 'node_leader',
            'status' => 'activo',
            'degree' => $request->degree,
            'postgraduate' => $request->postgraduate,
            'expertise_area' => $request->expertise_area,
            'research_work' => $request->research_work,
            'profile_picture' => $request->profile_picture,
            'social_media' => $request->social_media ? json_encode($request->social_media) : null,
        ]);

        // Crear el Nodo
        $node = Node::create([
            'leader_id' => $user->id,
            'name' => $request->node_name,
            'type' => $decoded->node_type,
            'about' => $request->about,
            'country' => $request->country,
            'city' => $request->city,
            'status' => 'activo',
            'joined_in' => now()->year
        ]);

        // Actualizar la invitación como aceptada
        $invitation->update([
            'status' => 'aceptada', 
            'accepted_date' => now()
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Usuario y nodo registrados con éxito',
            'data' => [
                'user' => $user, 
                'node' => $node
            ]
        ], 201);
    }
}
