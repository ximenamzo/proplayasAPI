<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success($message = 'OK: Success', $data = [])
    {
        return response()->json([
            'status' => 200,
            'message' => $message,
            'data' => $data
        ], 200);
    }

    public static function created($message = 'Created: Recurso creado correctamente', $data = [])
    {
        return response()->json([
            'status' => 201,
            'message' => $message,
            'data' => $data
        ], 201);
    }

    public static function error($message = 'Error', $code = 400, $data = [])
    {
        return response()->json([
            'status' => $code,
            'message' => $message,
            'data' => $data ?? null
        ], $code);
    }

    public static function unauthenticated($message = 'Unauthenticated: Token inválido o usuario no autenticado')
    {
        return self::error($message, 401, null);
    }
    
    public static function unauthorized($message = 'Forbidden: No autorizado')
    {
        return self::error($message, 403, null);
    }

    public static function notFound($message = 'Not Found: Recurso no encontrado')
    {
        return self::error($message, 404, null);
    }

    public static function validationError($errors, $message = 'Error de validación')
    {
        return self::error($message, 422, ['errors' => $errors]);
    }

    public static function serverError($message = 'Error interno del servidor', $debug = null)
    {
        return self::error($message, 500, $debug ? ['debug' => $debug] : null);
    }

    
}
