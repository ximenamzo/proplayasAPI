<?php

namespace App\Exceptions;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use UnexpectedValueException;

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
        if ($exception instanceof ExpiredException) {
            return response()->json([
                'status' => 401, 
                'error' => 'Token expirado'
            ], 401);
        }

        if ($exception instanceof SignatureInvalidException) {
            return response()->json([
                'status' => 401, 
                'error' => 'Firma de token inválida'
            ], 401);
        }

        if ($exception instanceof BeforeValidException) {
            return response()->json([
                'status' => 401, 
                'error' => 'Token aún no es válido'
            ], 401);
        }

        if ($exception instanceof UnexpectedValueException) {
            return response()->json([
                'status' => 401, 
                'error' => 'Token mal formado o inválido'
            ], 401);
        }

        if ($exception instanceof UnexpectedValueException) {
            return response()->json([
                'status' => 401,
                'error' => 'Token mal formado o inválido',
                'debug' => app()->environment('local') ? $exception->getMessage() : null
            ], 401);
        }

        return parent::render($request, $exception);
    }
}
