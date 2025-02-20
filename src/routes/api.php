<?php

use App\Models\User;
use App\Http\Controllers\HomepageContentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
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
Route::get('/test', function (){
    return response()->json(['message' => 'API is working!']);
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

    // Crear un usuario
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => $request->role,
        'status' => 'activo',
    ]);

    // Verificar si el rol existe en la BD y asignarlo
    $role = Role::where('name', $request->role)->first();
    if ($role) {
        $user->assignRole($role);
    } else {
        return response()->json(['error' => 'Role not found'], 404);
    }

    return response()->json([
        'message' => 'User registered successfully',
        'user' => $user,
    ], 201);
});

// Inicio de sesión con autenticación y roles
Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|string|email',
        'password' => 'required|string',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    return response()->json([
        'token' => $user->createToken('auth_token')->plainTextToken,
        'token_type' => 'Bearer',
        'role' => $user->role,
    ]);
});

// Logout
Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    $request->user()->tokens()->delete();
    return response()->json(['message' => 'Logged out']);
});


/**----------------------------------------------------------------
 * 🔸 DASHBOARDS (PROTEGIDOS POR ROL)
 * Paneles específicos según el rol del usuario.
 * ----------------------------------------------------------------
 */
// Dashboard de administrador (solo admins pueden acceder)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/admin-dashboard', function (Request $request) {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json(['message' => 'Bienvenido Admin']);
    });

    Route::get('/node-dashboard', function (Request $request) {
        if (!$request->user()->isNodeLeader()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json(['message' => 'Bienvenido Líder de Nodo']);
    });

    Route::get('/member-dashboard', function (Request $request) {
        if (!$request->user()->isMember()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json(['message' => 'Bienvenido Miembro']);
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

/**
 * 🔹 CRUD: COLABORADORES
 * Aquí van las rutas para gestionar colaboradores y suscripciones a boletines.
 */

/**
 * 🔹 CRUD: PUBLICACIONES (LIBROS, ARTÍCULOS, WEBSERIES, NEWS, WEBINARS)
 * Aquí van las rutas para manejar contenido publicado en la plataforma.
 */
