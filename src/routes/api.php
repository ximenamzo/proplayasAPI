<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Validation\ValidationException;

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
    return $request->user();
});

// Ruta pública para comprobar que la API funciona
Route::get('/test', function (){
    return response()->json(['message' => 'API is working!']);
});

// Registro de usuarios
Route::post('/register', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    return response()->json(['message' => 'User registered successfully'], 201);
});

// Inicio de sesión y obtención de token
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
    ]);
});

// Ruta para obtener el usuario autenticado
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return response()->json(['user' => $request->user()]);
});

/** Route::middleware(['auth:sanctum', 'role:admin'])->get('/admin-dashboard', function (Request $request) {
*    return response()->json([
*        'message' => 'Bienvenido Admin',
*        'roles' => $request->user()->getRoleNames()
*    ]);
* });
*/

// Ruta de logout con manejo de error si no hay token
Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    if (!$request->user()) {
        return response()->json(['message' => 'No user authenticated'], 401);
    }

    $request->user()->tokens()->delete();
    return response()->json(['message' => 'Logged out']);
});

// Ruta protegida para administradores con manejo de errores
Route::middleware(['auth:sanctum', 'role:admin'])->get('/admin-dashboard', function (Request $request) {
    return response()->json(['message' => 'Bienvenido Admin']);
})->fallback(function () {
    return response()->json(['message' => 'Forbidden'], 403);
});