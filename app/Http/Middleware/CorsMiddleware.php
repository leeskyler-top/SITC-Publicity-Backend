<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if ($request->is('api/v1/files/*')) {
            return $next($request);
        }

        $allowedDomains = [
            'https://twxc-beta.leeskyler.top',
            'http://localhost:5173',
        ];

        $origin = $request->header('Origin');

        if (!$origin) {
            return $next($request);
        }

        if (!in_array($origin, $allowedDomains)) {
            return response()->json(['msg' => '未授权'], 401);
        }

        return $next($request)
            ->header('Access-Control-Allow-Origin', $origin)
            ->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, DELETE')
            ->header('Access-Control-Allow-Headers', 'Content-Type');
    }
}
