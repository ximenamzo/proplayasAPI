<?php

use App\Models\User;
use App\Helpers\JWTHandler;
use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\HomepageContentController;
use App\Http\Middleware\JWTMiddleware;
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


/**-------------------------------------------------------------------------
 * 🔹 AUTENTICACIÓN (REGISTER, LOGIN, LOGOUT)
 * Rutas para el registro de usuarios, inicio de sesión y cierre de sesión.
 * -------------------------------------------------------------------------
 */
// Registro de usuarios con asignación del rol
Route::post('/register', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
        'role' => 'required|in:admin,node_leader,member',
    ]);

    // Decodificar la contraseña base64
    $decodedPassword = base64_decode($request->password);

    // Crear usuario con contraseña hasheada
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($decodedPassword),
        'role' => $request->role,
        'status' => 'activo',
    ]);

    // Verificar si el rol existe en la BD y asignarlo
    $role = Role::where('name', $request->role)->first();
    if ($role) {
        $user->assignRole($role);
    } else {
        return response()->json([
            'status' => 400, 
            'error' => 'Role not found'
        ], 400);
    }

    return response()->json([
        'status' => 201, 
        'message' => 'User registered successfully', 
        'data' => $user
    ], 201);
});

// 🔹 Inicio de sesión con autenticación y roles
Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|string|email',
        'password' => 'required|string',
    ]);

    $user = User::where('email', $request->email)->first();

    // Decodificar la contraseña base64 antes de validarla
    $decodedPassword = base64_decode($request->password);

    if (!$user || !Hash::check($decodedPassword, $user->password)) {
        return response()->json([
            'status' => 401,
            'error' => 'Invalid credentials'
        ], 401);
    }

    return response()->json([
        'status' => 200,
        'message' => 'Login successful',
        'data' => [
            //'token' => $user->createToken('auth_token')->plainTextToken,
            'token' => JWTHandler::createToken($user),
            'role' => $user->role
        ]
    ], 200);
});

// Logout
Route::middleware(['jwt.auth'])->post('/logout', function (Request $request) {
    //$request->user()->tokens()->delete();
    return response()->json([
        'status' => 200, 
        'message' => 'Logged out successfully'
    ], 200);
});


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
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('homepage-content')->group(function () {
    Route::get('/', [HomepageContentController::class, 'index']);
    Route::get('/{id}', [HomepageContentController::class, 'show']);
    Route::post('/', [HomepageContentController::class, 'store']);
    Route::put('/{id}', [HomepageContentController::class, 'update']);
    Route::delete('/{id}', [HomepageContentController::class, 'destroy']);
});


/**
 * 🔹 CRUD: NODOS
 * Aquí van las rutas para gestionar los nodos (acceso según rol).
 */

/**
 * 🔹 CRUD: MIEMBROS
 * Aquí van las rutas para gestionar los miembros de nodos.
 */

/**
 * 🔹 CRUD: INVITACIONES
 * Aquí van las rutas para manejar invitaciones a nodos y miembros.
 */

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

    
    // ADMIN: Ver todos los colaboradores
    Route::middleware(['auth:sanctum', 'role:admin'])->get('/', [CollaboratorController::class, 'index']);

    // ADMIN: Actualizar estado o suscripción de un colaborador
    Route::middleware(['auth:sanctum', 'role:admin'])->put('/{id}', [CollaboratorController::class, 'update']);

    // ADMIN: Desactivar colaborador (soft delete)
    Route::middleware(['auth:sanctum', 'role:admin'])->delete('/{id}', [CollaboratorController::class, 'destroy']);
});

/**
 * 🔹 CRUD: PUBLICACIONES (LIBROS, ARTÍCULOS, WEBSERIES, NEWS, WEBINARS)
 * Aquí van las rutas para manejar contenido publicado en la plataforma.
 */
