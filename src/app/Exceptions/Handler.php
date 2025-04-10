<?php

namespace App\Exceptions;

use App\Helpers\ApiResponse;
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

        if ($exception instanceof UnexpectedValueException) {
            return ApiResponse::error(
                'Token mal formado o inválido',
                401,
                app()->environment('local') ? ['debug' => $exception->getMessage()] : null
            );
        }

        return parent::render($request, $exception);
    }
}
