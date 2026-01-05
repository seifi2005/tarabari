<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receptor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReceptorAuthController extends Controller
{
    /**
     * Get JWT Token for Receptor
     */
    public function getToken(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // پیدا کردن receptor
        $receptor = Receptor::where('username', $request->username)->first();

        if (!$receptor || !Hash::check($request->password, $receptor->password)) {
            return response()->json([
                'message' => trans('messages.invalid_credentials'),
            ], 401);
        }

        // پیدا کردن کاربر مرتبط
        $user = User::where('receptor_id', $receptor->id)
            ->where('role', 'receptor')
            ->first();

        if (!$user) {
            return response()->json([
                'message' => trans('messages.user_not_found_for_receptor'),
            ], 404);
        }

        // بررسی IP مجاز (اگر تنظیم شده)
        if ($receptor->allowed_ip && $request->ip() !== $receptor->allowed_ip) {
            return response()->json([
                'message' => 'IP address not allowed',
            ], 403);
        }

        // تولید JWT Token
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => trans('messages.token_generated_successfully'),
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60, // به ثانیه
        ]);
    }

    /**
     * Get authenticated receptor (با JWT)
     */
    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user || !$user->isReceptor()) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 401);
            }

            $user->load('receptor');

            return response()->json([
                'user' => $user,
                'receptor' => $user->receptor,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('messages.token_invalid_or_expired'),
            ], 401);
        }
    }

    /**
     * Refresh JWT Token
     */
    public function refresh()
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            
            return response()->json([
                'message' => 'Token refreshed successfully',
                'token' => $token,
                'token_type' => 'Bearer',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('messages.token_refresh_failed'),
            ], 401);
        }
    }
}

