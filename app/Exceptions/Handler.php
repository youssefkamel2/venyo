<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log; // Import the Log facade
use App\Traits\ResponseTrait; // Use the ResponseTrait

class Handler extends ExceptionHandler
{
    use ResponseTrait; // Include the response trait

    protected $levels = [
        //
    ];

    protected $dontReport = [
        //
    ];
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            Log::error($e->getMessage(), ['exception' => $e, 'code' => $e->getCode()]);
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return $this->error('Unauthorized access. Please log in.', 401);
    }

    public function render($request, Throwable $exception) // Change Exception to Throwable
    {
        $response = [
            'success' => false,
            'message' => 'An error occurred. Please try again later.',
            'code' => 500 // Default error code
        ];

        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->error('Resource not found.', 404);
        }

        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return $this->error('The requested URL was not found on this server.', 404);
        }

        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            return $this->unauthenticated($request, $exception);
        }

        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return $this->error('You do not have permission to perform this action.', 403);
        }
        

        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            return $this->error($exception->getMessage(), $exception->getStatusCode());
        }

        return $this->error($response['message'], 500);
    }
}
