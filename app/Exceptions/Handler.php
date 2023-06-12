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
            return response()->json(['msg' => "未授权"], 401);
        });
        $this->renderable(function (NotFoundHttpException $e) {
            return response()->json(['msg' => "试图访问的API未找到"], 404);
        });
        $this->renderable(function (MethodNotAllowedHttpException $e) {
            return response()->json(['msg' => "此HTTP请求模式不被允许"], 405);
        });
        $this->renderable(function (TooManyRequestsHttpException $e) {
            return response()->json(['msg' => "请求过多"], 429);
        });
    }
}
