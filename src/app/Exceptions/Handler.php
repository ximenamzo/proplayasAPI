<?php

namespace App\Exceptions;

use App\Helpers\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Throwable;
use UnexpectedValueException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // 🔐 JWT específicos
        if ($exception instanceof ExpiredException) {
            return ApiResponse::unauthenticated('Token expirado', 401);
        }

        if ($exception instanceof SignatureInvalidException) {
            return ApiResponse::unauthenticated('Firma de token inválida', 401);
        }

        if ($exception instanceof BeforeValidException) {
            return ApiResponse::unauthenticated('Token no válido', 401);
        }

        if ($exception instanceof UnexpectedValueException) {
            return ApiResponse::unauthenticated('Token mal formado o inválido', 401);
        }

        // 📦 Archivo demasiado grande
        if ($exception instanceof PostTooLargeException) {
            return ApiResponse::error('El archivo enviado excede el tamaño permitido.', 413);
        }

        // 🔒 No autenticado (Laravel)
        if ($exception instanceof AuthenticationException) {
            return ApiResponse::unauthenticated('No autenticado');
        }

        // 🔒 No autorizado
        if ($exception instanceof UnauthorizedHttpException || $exception instanceof AccessDeniedHttpException) {
            return ApiResponse::unauthorized();
        }

        // 📭 Recurso no encontrado
        if ($exception instanceof NotFoundHttpException) {
            return ApiResponse::notFound('Ruta o recurso no encontrado');
        }

        // ⛔ Método HTTP no permitido
        if ($exception instanceof MethodNotAllowedHttpException) {
            return ApiResponse::error('Método HTTP no permitido', 405);
        }

        // 🕒 Demasiadas peticiones
        if ($exception instanceof TooManyRequestsHttpException) {
            return ApiResponse::error('Demasiadas peticiones', 429);
        }

        // 🛡️ Operación sospechosa (URL incorrecta, por ejemplo)
        if ($exception instanceof SuspiciousOperationException) {
            return ApiResponse::error('Operación sospechosa detectada', 400);
        }

        // 🔧 Para errores inesperados
        if (!($exception instanceof HttpExceptionInterface)) {
            return ApiResponse::serverError(
                'Error inesperado en el servidor', 500,
                app()->environment('local') ? $exception->getMessage() : null
            );
        }

        // ⏮️ Fallback: comportamiento por defecto
        return parent::render($request, $exception);
    }

    public function invalidJson($request, ValidationException $exception): JsonResponse
    {
        return ApiResponse::error('Los datos enviados no son válidos.', 422, ['errors' => $exception->errors(),]);        
    }
}
