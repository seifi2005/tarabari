<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Send OTP for login (برای super_admin و operator)
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string|regex:/^09\d{9}$/',
        ]);

        $user = User::where('mobile', $request->mobile)
            ->whereIn('role', ['super_admin', 'operator'])
            ->first();

        if (!$user) {
            return response()->json([
                'message' => trans('messages.user_not_found'),
            ], 404);
        }

        $result = $this->otpService->generateOtp($request->mobile);

        return response()->json([
            'message' => trans('messages.otp_sent_successfully'),
        ]);
    }

    /**
     * Login with OTP (برای super_admin و operator)
     */
    public function loginWithOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string|regex:/^09\d{9}$/',
            'otp' => 'required|string|size:6',
        ]);

        $user = User::where('mobile', $request->mobile)
            ->whereIn('role', ['super_admin', 'operator'])
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'mobile' => [trans('messages.user_not_found')],
            ]);
        }

        if (!$this->otpService->verifyOtp($request->mobile, $request->otp)) {
            throw ValidationException::withMessages([
                'otp' => [trans('messages.invalid_otp')],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => trans('messages.login_successful'),
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Login with username and password (برای super_admin و operator)
     */
    public function loginWithPassword(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required',
        ]);

        $user = User::where('username', $request->username)
            ->whereIn('role', ['super_admin', 'operator'])
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => [trans('messages.credentials_incorrect')],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => trans('messages.login_successful'),
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout (Sanctum)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => trans('messages.logout_successful'),
        ]);
    }

    /**
     * Get authenticated user (Sanctum)
     */
    public function me(Request $request)
    {
        $user = $request->user();
        
        if ($user->isReceptor() && $user->receptor) {
            $user->load('receptor');
        }

        return response()->json([
            'user' => $user,
        ]);
    }
}