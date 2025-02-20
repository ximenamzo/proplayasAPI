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
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return response()->json($request->user());
});

// Ruta pública para comprobar que la API funciona
Route::get('/test', function (){
    return response()->json(['message' => 'API is working!']);
});

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


// Dashboard de administrador (solo admins pueden acceder)
Route::middleware(['auth:sanctum'])->get('/admin-dashboard', function (Request $request) {
    if (!$request->user()->isAdmin()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    return response()->json(['message' => 'Bienvenido Admin']);
});

// Dashboard de líderes de nodo (solo node_leader puede acceder)
Route::middleware(['auth:sanctum'])->get('/node-dashboard', function (Request $request) {
    if (!$request->user()->isNodeLeader()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    return response()->json(['message' => 'Bienvenido Líder de Nodo']);
});

// Dashboard de miembros (solo miembros pueden acceder)
Route::middleware(['auth:sanctum'])->get('/member-dashboard', function (Request $request) {
    if (!$request->user()->isMember()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    return response()->json(['message' => 'Bienvenido Miembro']);
});

/**
 * HOMEPAGE CONTENT
 * Grupo de rutas protegidas para Admin
 */
/*Route::middleware(['auth:sanctum', RoleMiddleware::class . ':admin'])->group(function () {
    Route::get('/homepage-content', [HomepageContentController::class, 'index']);
    Route::get('/homepage-content/{id}', [HomepageContentController::class, 'show']);
    Route::post('/homepage-content', [HomepageContentController::class, 'store']);
    Route::put('/homepage-content/{id}', [HomepageContentController::class, 'update']);
    Route::delete('/homepage-content/{id}', [HomepageContentController::class, 'destroy']);
});*/

// Ruta que si funciona:
/*Route::middleware(['auth:sanctum', 'role:admin'])->post('/homepage-content', function (Request $request) {
    return response()->json(['message' => 'Contenido actualizado']);
});*/
Route::middleware(['auth:sanctum', 'role:admin'])
     ->get('/homepage-content', [HomepageContentController::class, 'index']);
Route::middleware(['auth:sanctum', 'role:admin'])
     ->get('/homepage-content/{id}', [HomepageContentController::class, 'show']);
Route::middleware(['auth:sanctum', 'role:admin'])
     ->post('/homepage-content', [HomepageContentController::class, 'store']);
Route::middleware(['auth:sanctum', 'role:admin'])
     ->put('/homepage-content/{id}', [HomepageContentController::class, 'update']);
Route::middleware(['auth:sanctum', 'role:admin'])
     ->delete('/homepage-content/{id}', [HomepageContentController::class, 'destroy']);

