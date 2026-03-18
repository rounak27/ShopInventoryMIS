<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    // public function handle($request, Closure $next)
    // {
    //     // dd("JWT Middleware triggered");
    //     try {
    //         $user = JWTAuth::parseToken()->authenticate();
    //         dd($user);
    //         if (!$user) {
    //             return response()->json(['message' => 'User not found'], 401);
    //         }
    //     } catch (JWTException $e) {
    //         dd("JWT Exception: " . $e->getMessage());
    //         return response()->json(['message' => 'Unauthenticated.'], 401);
    //     }

    //     return $next($request);
    // }
    public function handle(Request $request, Closure $next): Response
    {
        // Some Apache/PHP setups expose Authorization only in server vars.
        $authHeader = $request->header('Authorization')
            ?? $request->server('HTTP_AUTHORIZATION')
            ?? $request->server('REDIRECT_HTTP_AUTHORIZATION');

        if ($authHeader && ! $request->headers->has('Authorization')) {
            $request->headers->set('Authorization', $authHeader);
        }

        try {
            if (! $token = JWTAuth::getToken()) {
                return response()->json(['message' => 'Token not provided'], 401);
            }

            $user = JWTAuth::authenticate($token);
            
            if (! $user) {
                return response()->json(['message' => 'User not found'], 401);
            }
        } catch (TokenExpiredException $e) {
            return response()->json([
                'message' => 'Token has expired',
                'error' => 'token_expired',
            ], 401);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
