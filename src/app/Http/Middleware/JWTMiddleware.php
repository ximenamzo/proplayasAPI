<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\JWTHandler;
use App\Helpers\ApiResponse;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Exception;
use UnexpectedValueException;

class JWTMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token && $request->routeIs('test')) {
            // Es ruta pública, continuar
            return $next($request);
        } else if (!$token) {
            return ApiResponse::unauthenticated('Token not provided', 401);
        }

        try {
            $decoded = JWTHandler::decodeToken($token);
            #$request->user = $decoded;
            Log::info("Token decodificado correctamente:", $decoded);

            // 🔹 Convertir a objeto estándar
            $userObject = json_decode(json_encode($decoded), false);

            // 🔹 Asegurar que Laravel reconozca bien el usuario
            $request->setUserResolver(fn() => $userObject);
            $request->attributes->set('user', $userObject);

            Log::info("Usuario autenticado en Middleware:", (array) $userObject);           
        } catch (ExpiredException $e) {
            return ApiResponse::unauthenticated('Token expired', 401);
        } catch (SignatureInvalidException $e) {
            return ApiResponse::unauthenticated('Token signature invalid', 401);
        } catch (BeforeValidException $e) {
            return ApiResponse::unauthenticated('Token not valid yet', 401);
        } catch (UnexpectedValueException $e) {
            return ApiResponse::unauthenticated('Token structure invalid', 401);
        } catch (\Throwable $e) {
            Log::error("JWT decoding error: " . $e->getMessage());
            return ApiResponse::unauthenticated('Token invalid', 401);
        } catch (Exception $e) {
            Log::error("Error al decodificar el token: " . $e->getMessage());
            return ApiResponse::unauthenticated('Token invalid', 401);
        }

        return $next($request);
    }
}
