<?php

use App\Models\User;
use App\Models\Node;
use App\Models\Member;
use App\Helpers\JWTHandler;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\HomepageContentController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\NodeController;
use App\Http\Controllers\MemberController;
use App\Http\Middleware\JWTMiddleware;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| 💠 API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
| Aquí se regisran todas las rutas de la API. Están organizadas en
| secciones con comentarios para facilitar la navegación y mantenimiento.
*/

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {return response()->json($request->user());});

// Ruta pública para comprobar que la API funciona
Route::get('/test', function () {
    return response()->json([
        'status' => 200, 
        'message' => 'API is working!'
    ], 200);
});

// Ruta pública para enviar un correo de prueba
Route::get('/test-email', function () {
    $sent = MailService::sendMail('test@example.com', 'Prueba', 'Este es un correo de prueba');
    return response()->json(['success' => $sent]);
});



/**-------------------------------------------------------------------------
 * 🔹 AUTENTICACIÓN (REGISTER, LOGIN, LOGOUT)
 * Rutas para el registro de usuarios, inicio de sesión y cierre de sesión.
 * -------------------------------------------------------------------------
 */
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('jwt.auth')->post('/logout', [AuthController::class, 'logout']);
Route::middleware('jwt.auth')->post('/logout-all', [AuthController::class, 'logoutAll']);


/**----------------------------------------------------------------
 * 🔸 DASHBOARDS (PROTEGIDOS POR ROL)
 * Paneles específicos según el rol del usuario.
 * ----------------------------------------------------------------
 */
Route::middleware('jwt.auth')->group(function () {
    Route::get('/admin-dashboard', function (Request $request) {
        return $request->user->role === 'admin'
            ? response()->json([
                'status' => 200, 
                'message' => 'Bienvenido Admin'
                ])
            : response()->json([
                'status' => 403, 
                'error' => 
                'Unauthorized'
            ], 403);
    });

    Route::get('/node-dashboard', function (Request $request) {
        return $request->user->role === 'node_leader'
            ? response()->json([
                'status' => 200, 
                'message' => 'Bienvenido Líder de Nodo'
                ])
            : response()->json([
                'status' => 403, 
                'error' => 'Unauthorized'
            ], 403);
    });

    Route::get('/member-dashboard', function (Request $request) {
        return $request->user->role === 'member'
            ? response()->json([
                'status' => 200, 
                'message' => 'Bienvenido Miembro'
                ])
            : response()->json([
                'status' => 403, 
                'error' => 'Unauthorized'
            ], 403);
    });
});

/**--------------------------------------------------------------------
 * 🔹 CRUD: HOMEPAGE CONTENT (SOLO PARA ADMIN)
 * --------------------------------------------------------------------
 */
Route::middleware(['jwt.auth'])->prefix('homepage-content')->group(function () {
    Route::get('/', [HomepageContentController::class, 'index']);
    Route::get('/{id}', [HomepageContentController::class, 'show']);
    Route::post('/', [HomepageContentController::class, 'store']);
    Route::put('/{id}', [HomepageContentController::class, 'update']);
    Route::delete('/{id}', [HomepageContentController::class, 'destroy']);
});


/**
 * 🔹 CRUD: INVITACIONES
 * Aquí van las rutas para manejar invitaciones a nodos y miembros.
 */
Route::prefix('invitations')->group(function () {
    Route::get('/{token}', [InvitationController::class, 'validateInvitation']);
    Route::post('/accept', [InvitationController::class, 'acceptInvitation']);
});


/**
 * 🔹 CRUD: NODOS
 * Aquí van las rutas para gestionar los nodos (acceso según rol).
 */
Route::prefix('nodes')->group(function () {
    Route::get('/', [NodeController::class, 'index']); // Ver todos los nodos
    Route::get('/{id}', [NodeController::class, 'show']); // Ver un nodo

    Route::middleware(['jwt.auth'])->group(function () {
        Route::put('/{id}', [NodeController::class, 'update']);
        Route::delete('/{id}', [NodeController::class, 'destroy']);
        
        // Invitación a líder a nodo
        Route::post('/invite', [InvitationController::class, 'inviteNodeLeader']);
    });
});


/**
 * 🔹 CRUD: MIEMBROS
 * Aquí van las rutas para gestionar los miembros de nodos.
 */
Route::prefix('members')->group(function () {
    Route::get('/', [MemberController::class, 'index']); // Ver todos los miembros
    Route::get('/{id}', [MemberController::class, 'show']); // Ver un miembro

    Route::middleware(['jwt.auth'])->group(function () {
        Route::put('/{id}', [MemberController::class, 'update']);
        Route::delete('/{id}', [MemberController::class, 'destroy']);

        // Admin reasigna un miembro a otro nodo
        Route::put('/{id}/reassign', [MemberController::class, 'reassignNode']);

        // Invitación a miembro
        Route::post('/invite', [InvitationController::class, 'inviteMember']);
    });
});


/**----------------------------------------------------------------------------
 * 🔹 CRUD: COLABORADORES
 * Aquí van las rutas para gestionar colaboradores y suscripciones a boletines.
 * ----------------------------------------------------------------------------
 */
Route::prefix('collaborators')->group(function () {
    // Registrar un nuevo colaborador (PÚBLICO)
    Route::post('/', [CollaboratorController::class, 'store']);

    // Unsubscribirse de boletines (PÚBLICO)
    Route::post('/unsubscribe', [CollaboratorController::class, 'unsubscribe']);

    // ADMIN
    Route::middleware(['jwt.auth'])->group(function () {
        // ADMIN: Ver todos los colaboradores
        Route::get('/', [CollaboratorController::class, 'index']);

        // ADMIN: Actualizar estado o suscripción de un colaborador
        Route::put('/{id}', [CollaboratorController::class, 'update']);

        // ADMIN: Desactivar colaborador (soft delete)
        Route::delete('/{id}', [CollaboratorController::class, 'destroy']);
    });
});

/**
 * 🔹 CRUD: PUBLICACIONES (LIBROS, ARTÍCULOS, WEBSERIES, NEWS, WEBINARS)
 * Aquí van las rutas para manejar contenido publicado en la plataforma.
 */
