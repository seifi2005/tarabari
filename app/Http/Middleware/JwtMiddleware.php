<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user || !$user->isReceptor()) {
                return response()->json([
                    'message' => 'Unauthorized - Receptor access only',
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Token invalid or expired',
            ], 401);
        }

        return $next($request);
    }
}

