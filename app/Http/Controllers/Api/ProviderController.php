<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

class ProviderController extends Controller
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

    /**
     * لیست provider ها
     */
    public function index(Request $request)
    {
        $query = Provider::query();

        // فیلتر بر اساس is_active
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // جستجو
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $providers = $query->orderBy('name')->paginate(15);

        // پنهان کردن اطلاعات حساس
        $providers->getCollection()->transform(function ($provider) {
            $provider->makeHidden(['api_password', 'api_key']);
            return $provider;
        });

        return response()->json($providers);
    }

    /**
     * ایجاد provider جدید
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:providers',
            'api_base_url' => 'nullable|url|max:500',
            'api_username' => 'nullable|string|max:255',
            'api_password' => 'nullable|string|max:255',
            'api_key' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'config' => 'nullable|array',
        ]);

        $provider = Provider::create($request->only([
            'name',
            'code',
            'api_base_url',
            'api_username',
            'api_password',
            'api_key',
            'is_active',
            'config',
        ]));

        $provider->makeHidden(['api_password', 'api_key']);

        return response()->json([
            'message' => 'Provider created successfully',
            'provider' => $provider,
        ], 201);
    }

    /**
     * مشاهده provider
     */
    public function show($id)
    {
        $provider = Provider::findOrFail($id);
        $provider->makeHidden(['api_password', 'api_key']);

        return response()->json($provider);
    }

    /**
     * آپدیت provider
     */
    public function update(Request $request, $id)
    {
        $provider = Provider::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('providers')->ignore($provider->id)],
            'api_base_url' => 'nullable|url|max:500',
            'api_username' => 'nullable|string|max:255',
            'api_password' => 'nullable|string|max:255',
            'api_key' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'config' => 'nullable|array',
        ]);

        $data = $request->only([
            'name',
            'code',
            'api_base_url',
            'api_username',
            'api_password',
            'api_key',
            'is_active',
            'config',
        ]);

        // اگر password خالی است، تغییر نمی‌دهیم
        if (isset($data['api_password']) && empty($data['api_password'])) {
            unset($data['api_password']);
        }

        $provider->update($data);
        $provider->makeHidden(['api_password', 'api_key']);

        return response()->json([
            'message' => 'Provider updated successfully',
            'provider' => $provider,
        ]);
    }

    /**
     * حذف provider
     */
    public function destroy($id)
    {
        $provider = Provider::findOrFail($id);

        // بررسی اینکه آیا shipment ای با این provider وجود دارد
        if ($provider->shipments()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete provider with existing shipments',
            ], 400);
        }

        $provider->delete();

        return response()->json([
            'message' => 'Provider deleted successfully',
        ]);
    }
}

