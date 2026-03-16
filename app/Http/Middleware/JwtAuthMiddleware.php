<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
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
    public function handle($request, Closure $next)
    {
        // Debug: Check if the header is even arriving at your PHP script
        dd($request->header('Authorization')); 

        try {
            // Force the library to look at the bearer token specifically
            if (!$token = JWTAuth::getToken()) {
                return response()->json(['message' => 'Token not provided'], 401);
            }

            $user = JWTAuth::authenticate($token);
            
            if (!$user) {
                return response()->json(['message' => 'User not found'], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'debug_error' => $e->getMessage() // This will tell us EXACTLY why it failed
            ], 401);
        }

        return $next($request);
    }
}
