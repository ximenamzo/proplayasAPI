<?php

use App\Models\User;
use App\Models\Node;
use App\Models\Member;
use App\Helpers\JWTHandler;
use App\Helpers\ApiResponse;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\HomepageContentController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\NodeController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\JWTMiddleware;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\EventController;
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
    return ApiResponse::success('API is working!');
});

// Ruta pública para enviar un correo de prueba
Route::get('/test-email', function () {
    $sent = MailService::sendMail('test@example.com', 'Prueba', 'Este es un correo de prueba');
    return ApiResponse::success('Success: Email sent', $sent);
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
Route::middleware('jwt.auth')->post('/auth/refresh', [AuthController::class, 'refresh']);


/**----------------------------------------------------------------
 * 🔸 DASHBOARDS (PROTEGIDOS POR ROL)
 * Paneles específicos según el rol del usuario.
 * ----------------------------------------------------------------
 */
Route::middleware('jwt.auth')->group(function () {
    Route::get('/admin-dashboard', function (Request $request) {
        return $request->user->role === 'admin'
            ? ApiResponse::success('Bienvenido Admin')
            : ApiResponse::unauthorized();
    });

    Route::get('/node-dashboard', function (Request $request) {
        return $request->user->role === 'node_leader'
            ? ApiResponse::success('Bienvenido Líder de Nodo')
            : ApiResponse::unauthorized();
    });

    Route::get('/member-dashboard', function (Request $request) {
        return $request->user->role === 'member'
            ? ApiResponse::success('Bienvenido Miembro')
            : ApiResponse::unauthorized();
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
 * 🔹 CRUD: ADMINS
 * Rutas para gestion de admins (solo permiso de admins).
 */
Route::middleware(['jwt.auth'])->prefix('admins')->group(function () {
    Route::get('/', [AdminController::class, 'index']); // Lista de todos los admins
    Route::get('/{id}', [AdminController::class, 'show']); // Ver un admin específico
    Route::post('/invite', [InvitationController::class, 'inviteAdmin']); // Invitar un nuevo admin
    Route::put('/{id}', [AdminController::class, 'update']); // Editar admin
    Route::delete('/{id}', [AdminController::class, 'destroy']); // Eliminar un admin (solo desde consola)
});


/**
 * 🔹 CRUD: NODOS
 * Aquí van las rutas para gestionar los nodos (acceso según rol).
 */
Route::get('/nodes', [NodeController::class, 'index']); // Ver todos los nodos

Route::prefix('node')->group(function () {
    //Route::get('/', [NodeController::class, 'index']); // Ver todos los nodos
    Route::get('/{identifier}', [NodeController::class, 'show']); // Ver un nodo
    //Route::get('/code/{code}', [NodeController::class, 'showByCode']); // Ver un nodo por su código
    Route::get('/members/{identifier}', [UserController::class, 'listByNode']);// Listar miembros por ID o código de nodo

    Route::middleware(['jwt.auth'])->group(function () {
        Route::put('/{id}', [NodeController::class, 'update']); // Node leader edita su nodo
        Route::delete('/{id}', [NodeController::class, 'destroy']); // Admin elimina nodo (soft delete)
        Route::put('/{id}/reassign-leader', [NodeController::class, 'reassignLeader']); // Admin reasigna líder
        
        // Invitación a líder a nodo
        Route::post('/invite', [InvitationController::class, 'inviteNodeLeader']);
    });
});


/**
 * 🔹 CRUD: USERS (NODE LEADERS Y MIEMBROS)
 * Acceso público a perfiles básicos de miembros o node leaders
 */

// Dev: listar todos los usuarios (solo local)
Route::get('/users', [UserController::class, 'index']);
// Listar filtrado por nodo (solo admin)
Route::middleware('jwt.auth')->get('/usersByNode', [UserController::class, 'listAllMembers']);

Route::prefix('user')->group(function () {
    // Requieren autenticación (JWT)
    Route::middleware('jwt.auth')->group(function () {
        Route::post('/upload-profile-picture', [UserController::class, 'uploadProfilePicture']);
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);

        Route::put('/{id}', [UserController::class, 'toggleStatus']); // Toggle activar/desactivar usuario
        Route::delete('/{id}', [UserController::class, 'destroy']); // Eliminar usuario permanentemente
    });
    
    // Obtener usuario por ID o username (público o autenticado)
    Route::get('/{identifier}', [UserController::class, 'show']);
});


/** 
 * 🔹 CRUD: MIEMBROS
 * Aquí van las rutas para gestionar los miembros de nodos.
 */
Route::get('/members', [MemberController::class, 'index']); // Ver todos los miembros !
Route::prefix('member')->group(function () {
    Route::get('/{id}', [MemberController::class, 'show']); // Ver un miembro !

    Route::middleware(['jwt.auth'])->group(function () {
        Route::put('/{id}', [MemberController::class, 'toggleStatus']); // Toggle activar/desactivar miembro
        Route::delete('/{id}', [MemberController::class, 'removeFromNode']); // Eliminar al miembro de su nodo

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
 * 🔹 CRUDs DE CONTENIDO (PUBLICACIONES, LIBROS, WEBSERIES, eventS, NEWS)  
 * Aquí van las rutas para manejar contenido publicado en la plataforma.
 */

/** CRUD: PUBLICACIONES (boletines, guías, artículos) */
Route::get('/publications', [PublicationController::class, 'index']); // público y usuarios logueados

Route::prefix('publication')->group(function () {

    Route::middleware('jwt.auth')->group(function () {
        Route::post('/', [PublicationController::class, 'store']); // crear
        Route::put('/{id}/toggle-status', [PublicationController::class, 'toggleStatus']); // alternar público/archivado
        Route::put('/{id}', [PublicationController::class, 'update']); // editar
        Route::delete('/{id}', [PublicationController::class, 'destroy']); // eliminación permanente
    });

    Route::get('/{id}', [PublicationController::class, 'show']); // ver detalle
});


/** CRUD: EVENTOS (webinars, charlas, sesiones) */
Route::get('/events', [EventController::class, 'index']); // público y usuarios logueados

Route::prefix('event')->group(function () {

    Route::middleware('jwt.auth')->group(function () {
        Route::post('/', [EventController::class, 'store']); // crear
        Route::put('/{id}/toggle-status', [EventController::class, 'toggleStatus']); // alternar público/archivado
        Route::put('/{id}', [EventController::class, 'update']); // editar
        Route::delete('/{id}', [EventController::class, 'destroy']); // eliminación permanente
    });
    
    Route::get('/{id}', [EventController::class, 'show']); // ver detalle
});
