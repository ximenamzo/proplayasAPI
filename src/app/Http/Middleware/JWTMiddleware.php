<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\JWTHandler;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Exception;

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

        if (!$token) {
            return response()->json([
                'status' => 401,
                'error' => 'Token not provided'
            ], 401);
        }

        try {
            $decoded = JWTHandler::decodeToken($token);
            $request->user = $decoded;
        } catch (ExpiredException $e) {
            return response()->json([
                'status' => 401,
                'error' => 'Token expired'
            ], 401);
        } catch (SignatureInvalidException $e) {
            return response()->json([
                'status' => 401,
                'error' => 'Token signature invalid'
            ], 401);
        } catch (Exception $e) {
            return response()->json([
                'status' => 401,
                'error' => 'Token invalid'
            ], 401);
        }

        return $next($request);
    }
}
