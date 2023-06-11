<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
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
//        $this->reportable(function (Throwable $e) {
//            //
//        });
        $this->renderable(function (AuthenticationException $e) {
            return response()->json(['msg' => "unauthorized"], 401);
        });
        $this->renderable(function (NotFoundHttpException $e) {
            return response()->json(['msg' => "not found"], 404);
        });
        $this->renderable(function (MethodNotAllowedHttpException $e) {
            return response()->json(['msg' => "method not allowed"], 405);
        });
        $this->renderable(function (TooManyRequestsHttpException $e) {
            return response()->json(['msg' => "too many request"], 429);
        });
    }
}
