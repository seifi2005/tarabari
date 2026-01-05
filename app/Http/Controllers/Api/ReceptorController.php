<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receptor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ReceptorController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->isSuperAdmin() && !auth()->user()->isOperator()) {
                return response()->json(['message' => trans('messages.unauthorized')], 403);
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $receptors = Receptor::with('user')->paginate(15);

        return response()->json($receptors);
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'mobile' => 'required|string|regex:/^09\d{9}$/|unique:receptors',
            'allowed_ip' => 'nullable|ip',
            'username' => 'required|string|unique:receptors',
            'password' => 'required|string|min:8',
            'orders_base_url' => 'nullable|url|max:500',
            'orders_auth_token' => 'nullable|string|max:500',
        ]);

        $receptor = Receptor::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'company_name' => $request->company_name,
            'mobile' => $request->mobile,
            'allowed_ip' => $request->allowed_ip,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'orders_base_url' => $request->orders_base_url,
            'orders_auth_token' => $request->orders_auth_token,
        ]);

        return response()->json([
            'message' => trans('messages.receptor_created'),
            'receptor' => $receptor->load('user'),
        ], 201);
    }

    public function show($id)
    {
        $receptor = Receptor::with('user')->findOrFail($id);

        return response()->json($receptor);
    }

    public function update(Request $request, $id)
    {
        $receptor = Receptor::findOrFail($id);

        $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'company_name' => 'sometimes|required|string|max:255',
            'mobile' => ['sometimes', 'required', 'string', 'regex:/^09\d{9}$/', Rule::unique('receptors')->ignore($receptor->id)],
            'allowed_ip' => 'nullable|ip',
            'username' => ['sometimes', 'required', 'string', Rule::unique('receptors')->ignore($receptor->id)],
            'password' => 'sometimes|string|min:8',
            'orders_base_url' => 'nullable|url|max:500',
            'orders_auth_token' => 'nullable|string|max:500',
        ]);

        $data = $request->only([
            'first_name', 
            'last_name', 
            'company_name', 
            'mobile', 
            'allowed_ip', 
            'username',
            'orders_base_url',
            'orders_auth_token',
        ]);

        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $receptor->update($data);

        return response()->json([
            'message' => trans('messages.receptor_updated'),
            'receptor' => $receptor->load('user'),
        ]);
    }

    public function destroy($id)
    {
        $receptor = Receptor::findOrFail($id);
        $receptor->delete();

        return response()->json([
            'message' => trans('messages.receptor_deleted'),
        ]);
    }

    /**
     * دریافت لیست provider های مجاز برای receptor
     */
    public function getProviders($id)
    {
        $receptor = Receptor::with('providers')->findOrFail($id);

        return response()->json([
            'receptor_id' => $receptor->id,
            'providers' => $receptor->providers->map(function ($provider) {
                $provider->makeHidden(['api_password', 'api_key']);
                return $provider;
            }),
        ]);
    }

    /**
     * تنظیم provider های مجاز برای receptor
     */
    public function attachProviders(Request $request, $id)
    {
        $request->validate([
            'provider_ids' => 'required|array',
            'provider_ids.*' => 'exists:providers,id',
        ]);

        $receptor = Receptor::findOrFail($id);

        // Sync providers (حذف قبلی‌ها و اضافه کردن جدیدها)
        $receptor->providers()->sync($request->provider_ids);

        $receptor->load('providers');

        return response()->json([
            'message' => 'Providers attached successfully',
            'receptor_id' => $receptor->id,
            'providers' => $receptor->providers->map(function ($provider) {
                $provider->makeHidden(['api_password', 'api_key']);
                return $provider;
            }),
        ]);
    }
}

