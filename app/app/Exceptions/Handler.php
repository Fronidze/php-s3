<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $exception) {
            return response()->json([
                'status' => false,
                'code' => 500,
                'message' => $exception->getMessage()
            ]);
        });
    }

    public function render($request, Throwable $e): Response
    {
        return new JsonResponse(
            [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ],
            500
        );
        //return parent::render($request, $e);
    }
}
