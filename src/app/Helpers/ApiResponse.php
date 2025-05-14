<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ApiResponse
{
    public static function success($message = 'OK: Success', $data = [])
    {
        return response()->json([
            'status' => 200,
            'message' => $message,
            'data' => self::sanitizeNulls($data)
        ], 200);
    }

    public static function created($message = 'Created: Recurso creado correctamente', $data = [])
    {
        return response()->json([
            'status' => 201,
            'message' => $message,
            'data' => self::sanitizeNulls($data)
        ], 201);
    }

    public static function error($message = 'Error', $code = 400, $data = [])
    {
        return response()->json([
            'status' => $code,
            'message' => $message,
            'data' => is_array($data) ? self::sanitizeNulls($data) : $data
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

    /** Reemplazar nulls por cadenas vacías (recursivamente) */
    private static function sanitizeNulls($data)
    {
        // Si es una colección de Laravel (Collection o Eloquent\Collection)
        if ($data instanceof \Illuminate\Support\Collection) {
            return $data->map(function ($item) {
                return self::sanitizeNulls($item);
            });
        }
    
        // Si es un modelo Eloquent, lo convertimos a array
        if ($data instanceof \Illuminate\Database\Eloquent\Model) {
            $data = $data->toArray();
        }
    
        // Si es un array, lo procesamos recursivamente
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeNulls'], $data);
        }
    
        // Si es un objeto genérico
        if (is_object($data)) {
            foreach ($data as $key => $value) {
                $data->$key = self::sanitizeNulls($value);
            }
            return $data;
        }
    
        // Si es null, lo convertimos a cadena vacía
        return $data === null ? "" : $data;
    }     
}
