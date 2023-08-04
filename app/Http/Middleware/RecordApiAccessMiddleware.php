<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RecordApiAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $response =  $next($request);
        $user = Auth::user();
        if ($user) {
            AccessHistory::create([
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_uid' => $user->uid,
                'request_url' => $request->fullUrl()
            ]);
        }
        return $response;
    }
}
