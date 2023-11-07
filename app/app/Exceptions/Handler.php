<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

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
        $this->reportable(function (Throwable $exception) {
            echo "<pre>"; print_r($exception->getMessage()); echo "</pre>"; die("Debug Fronidze");
            return response()->json([
                'status' => false,
                'code' => 500,
                'message' => $exception->getMessage()
            ]);
        });
    }
}
